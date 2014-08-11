<?php
$PluginInfo['CSSedit'] = array(
	'Name' => 'CSSedit',
	'Description' => 'Add additional CSS (LESS/SCSS) Rules through a Dashboard Style Editor.',
	'Version' => '0.3',
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
		if (C('Plugins.CSSedit.Stylesheet')) {
			$Sender->AddCssFile('cache/CSSedit/'.C('Plugins.CSSedit.Stylesheet'));
		}
	}

	//Adds the CSSedit Link to the Dashboard
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Appearance', T('CSSedit'), 'settings/cssedit', 'Garden.Settings.Manage');
	}

	//The editor page
	public function SettingsController_CSSedit_Create($Sender){
		$Sender->Permission('Garden.Settings.Manage');
		$StyleSheetPath = PATH_UPLOADS.'/CSSedit/';
		$StyleSheet = $StyleSheetPath.'source.css';
		//This is for compatibility with v0.1 and will be removed in 1.0
		$OldSheet = PATH_CACHE.'/CSSedit/source.css';
		if (!file_exists($StyleSheet) && file_exists($OldSheet)) {
			rename($OldSheet, $StyleSheet);
		}
		if($Sender->Form->IsPostBack()){
			//Process a form submission
			$FormValues = $Sender->Form->FormValues();
			$Source = GetValue('Style', $FormValues, '');
			$Preprocessor = GetValue('Preprocessor', $FormValues, 0);
			//Write files to the stylesheet directory
			if (!file_exists($StyleSheetPath))
				mkdir($StyleSheetPath, 0755, true);
			file_put_contents($StyleSheet, $Source);
			file_put_contents($StyleSheetPath.time().'.css', $Source);
			$this->limitRevisions();
			//Save the config values
			SaveToConfig('Plugins.CSSedit.Preprocessor', $Preprocessor);
			SaveToConfig('Plugins.CSSedit.AddOnMobile', GetValue('AddOnMobile', $FormValues));
			//try to build the stylesheet
			if ($this->makeCSS($Sender, $Source, $Preprocessor, time())) {
				$Sender->InformMessage('<span class="InformSprite Check"></span> '
						.T('Your changes have been saved.'), 'HasSprite');
			} else {
				$Sender->InformMessage('<span class="InformSprite Bug"></span> '
						.T('Compilation Error:'), 'Dismissable HasSprite');
			}
		} else {
			//Prepare the form
			$Sender->Form->SetValue('Preprocessor', C('Plugins.CSSedit.Preprocessor'));
			$Sender->Form->SetValue('AddOnMobile', C('Plugins.CSSedit.AddOnMobile'));
			if (file_exists($StyleSheet)) {
				$Source = file_get_contents($StyleSheet);
			}
		}
		//Render the editor page
		$Sender->Form->SetValue('Style', $Source);
		$Sender->AddSideMenu('settings/cssedit');
		$Sender->SetData('Title', T('CSS Editor'));
		$Sender->AddJsFile('ace.js','plugins/CSSedit/js/ace-min-noconflict');
		$Sender->AddJsFile('cssedit.js','plugins/CSSedit');
		$Sender->Render($this->GetView('cssedit.php'));
	}

	//Only keep the last 20 revisions in stylesheet directory
	protected function limitRevisions() {
		$revs = glob(PATH_UPLOADS.'/CSSedit/*.css');
		$revcount = count($revs);
		for ($i = 0; $revcount - $i > 21; $i++) {
			if (basename($revs[$i]) == 'source.css')
				continue;
			unlink($revs[$i]);
		}
	}

	//Compile and minify the stylesheet
	//teturn true on success and false on failure
	protected function makeCSS($Sender, $String, $Preprocessor, $Token) {
		if(!class_exists('Minify_CSS_Compressor'))
			include_once(dirname(__FILE__).'/lib/Compressor.php');
		//The token should be the creation timestamp
		$Filename = $Token.'.css';
		$CachePath = PATH_CACHE.'/CSSedit/';
		$FullPath = $CachePath.$Filename;
		if (!file_exists($CachePath))
			mkdir($CachePath, 0755, true);
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
		file_put_contents($FullPath, Minify_CSS_Compressor::process($String));
		if (C('Plugins.CSSedit.Stylesheet'))
			unlink($CachePath.C('Plugins.CSSedit.Stylesheet'));
		SaveToConfig('Plugins.CSSedit.Stylesheet', $Filename);
		return true;
	}
}
