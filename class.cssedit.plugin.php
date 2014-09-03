<?php
$PluginInfo['CSSedit'] = array(
	'Name' => 'CSSedit',
	'Description' => 'Adds a CSS (LESS/SCSS) style editor to the Dashboard.',
	'Version' => '1.0.2',
	'RequiredApplications' => array('Vanilla' => '2.0.18'),
	'Author' => 'Bleistivt',
	'AuthorUrl' => 'http://bleistivt.net',
	'SettingsUrl' => '/dashboard/settings/cssedit',
	'MobileFriendly' => true
);

class CSSeditPlugin extends Gdn_Plugin {

	//Adds the stylesheet to every page except the dashboard
	public function Base_Render_Before($Sender) {
		if ($Sender->MasterView != '' && $Sender->MasterView != 'default')
			return;
		if (IsMobile() && !C('Plugins.CSSedit.AddOnMobile'))
			return;
		$Preview = Gdn::Session()->Stash('CSSeditPreview', '', false);
		if ($Preview) {
			//Preview a stylesheet
			$Sender->AddCssFile('cache/CSSedit/'.$Preview);
			$Sender->InformMessage(Wrap('', 'span', 
					array('class' => 'InformSprite',
						'style' => 'background:url('.$this->GetWebResource('icon.png').') no-repeat;background-size:100%;margin:15px 0 0 1px;')
					)
				.Wrap(T('You are looking at a preview of your changes.'), 'p')
				.Wrap(Anchor(T('Return to the editor'), 'dashboard/settings/cssedit'), 'p'), 'HasSprite'
			);
		} elseif (C('Plugins.CSSedit.Stylesheet')) {
			//Finally, add the actual stylesheet to the page
			$Sender->AddCssFile('cache/CSSedit/'.C('Plugins.CSSedit.Stylesheet'));
		}
	}

	//Adds the CSSedit Link to the Dashboard
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Appearance', T('CSS Editor'), 'settings/cssedit', 'Garden.Settings.Manage');
	}

	//The editor page
	public function SettingsController_CSSedit_Create($Sender){
		$Sender->Permission('Garden.Settings.Manage');
		$Session = Gdn::Session();
		//Check if preview button was toggled
		$Preview = (val('Preview', $Sender->Form->FormValues(), false));
		$StyleSheetPath = PATH_UPLOADS.'/CSSedit/';
		$StyleSheet = ($Preview) ? $StyleSheetPath.'preview.css' : $StyleSheetPath.'source.css';
		if($Sender->Form->IsPostBack()){
			//Process a form submission
			$FormValues = $Sender->Form->FormValues();
			$Source = val('Style', $FormValues, '');
			$Preprocessor = val('Preprocessor', $FormValues, 0);
			//Write files to the stylesheet directory
			if (!file_exists($StyleSheetPath))
				mkdir($StyleSheetPath, 0755, true);
			//Don't save anything when preview was requested
			if (!$Preview) {
				file_put_contents($StyleSheet, $Source);
				file_put_contents($StyleSheetPath.time().'.css', $Source);
				$this->CleanUp();
			}
			//Save the config values
			SaveToConfig('Plugins.CSSedit.Preprocessor', $Preprocessor);
			SaveToConfig('Plugins.CSSedit.AddOnMobile', val('AddOnMobile', $FormValues));
			//try to build the stylesheet
			if ($this->makeCSS($Sender, $Source, $Preprocessor, time(), $Preview)) {
				$Sender->InformMessage('<span class="InformSprite Check"></span> '
						.T('Your changes have been saved.'), 'HasSprite');
			} else {
				$Sender->InformMessage('<span class="InformSprite Bug"></span> '
						.T('Compilation Error:'), 'Dismissable HasSprite');
				$Preview = false;
			}
		} else {
			//Prepare the form
			$Sender->Form->SetValue('Preprocessor', C('Plugins.CSSedit.Preprocessor'));
			$Sender->Form->SetValue('AddOnMobile', C('Plugins.CSSedit.AddOnMobile'));
			Gdn::Session()->Stash('CSSeditPreview');
			//Get uncompressed source from Session Stash
			$Preview = Gdn::Session()->Stash('CSSeditPreviewSource');
			if ($Preview) {
				$Source = file_get_contents($Preview);
				$Preview = false;
			} elseif (file_exists($StyleSheet)) {
				$Source = file_get_contents($StyleSheet);
			}
		}
		if ($Preview)
			Redirect('/');
		//Render the editor page
		$Sender->Form->SetValue('Style', $Source);
		$Sender->AddSideMenu('settings/cssedit');
		$Sender->SetData('Title', T('CSS Editor'));
		$Sender->AddJsFile('ace.js','plugins/CSSedit/js/ace-min-noconflict');
		$Sender->AddJsFile('cssedit.js','plugins/CSSedit');
		$Sender->Render($this->GetView('cssedit.php'));
	}

	protected function CleanUp() {
		//Only keep the last 25 revisions in the stylesheet directory 
		$revs = glob(PATH_UPLOADS.'/CSSedit/*.css');
		$revcount = count($revs);
		for ($i = 0; $revcount - $i > 26; $i++) {
			if (basename($revs[$i]) == 'source.css')
				continue;
			unlink($revs[$i]);
		}
		//Remove cached previews
		$CachePath = PATH_CACHE.'/CSSedit/';
		foreach (glob(PATH_CACHE.'/CSSedit/*preview.css') as $g)
			unlink($g);
		if (file_exists($CachePath.'previewsrc.css'))
			unlink($CachePath.'previewsrc.css');
	}

	//Compile and minify the stylesheet
	//returns true on success and false on failure
	protected function makeCSS($Sender, $String, $Preprocessor, $Token, $Preview) {
		if(!class_exists('Minify_CSS_Compressor'))
			include_once(dirname(__FILE__).'/lib/Compressor.php');
		//The token should be the creation timestamp
		$Filename = $Token.'.css';
		$CachePath = PATH_CACHE.'/CSSedit/';
		$FullPath = $CachePath.$Filename;
		if (!file_exists($CachePath))
			mkdir($CachePath, 0755, true);
		//Save uncompressed source when in preview mode
		if ($Preview) {
			file_put_contents($CachePath.'previewsrc.css', $String);
			Gdn::Session()->Stash('CSSeditPreviewSource', $CachePath.'previewsrc.css');
		}
		if ($Preprocessor == 1) {
			//compile less
			if(!class_exists('lessc'))
				include_once(dirname(__FILE__).'/lib/lessc.inc.php');
			$less = new lessc;
			try {
				$String = $less->compile($String);
			} catch (exception $e) {
				//send the error message to the editor page
				$Sender->InformMessage($e->getMessage(), 'Dismissable');
				return false;
			}
		} elseif ($Preprocessor == 2) {
			//compile scss
			if(!class_exists('scssc'))
				include_once(dirname(__FILE__).'/lib/scss.inc.php');
			$scss = new scssc();
			try {
				$String = $scss->compile($String);
			} catch (exception $e) {
				$Sender->InformMessage($e->getMessage(), 'Dismissable');
				return false;
			}
		}
		//minify and save the stylesheet
		$String = Minify_CSS_Compressor::process($String);
		if ($Preview) {
			file_put_contents($CachePath.$Token.'preview.css', $String);
			Gdn::Session()->Stash('CSSeditPreview', $Token.'preview.css');
		} else {
			file_put_contents($FullPath, $String);
			if (C('Plugins.CSSedit.Stylesheet'))
				@unlink($CachePath.C('Plugins.CSSedit.Stylesheet'));
			SaveToConfig('Plugins.CSSedit.Stylesheet', $Filename);
		}
		return true;
	}

}
