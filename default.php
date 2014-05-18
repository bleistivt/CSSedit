<?php
$PluginInfo['CSSedit'] = array(
	'Name' => 'CSSedit',
	'Description' => 'Add additional CSS (or LESS) Rules through a Dashboard Style Editor.',
	'Version' => '0.1',
	'RequiredApplications' => array('Vanilla' => '2.0.18'),
	'Author' => "Bleistivt",
	'AuthorUrl' => 'http://bleistivt.net',
	'SettingsUrl' => '/dashboard/settings/cssedit',
	'MobileFriendy' => TRUE
);

if(!class_exists('lessc')){
	include_once(dirname(__FILE__).DS.'lib'.DS.'lessc.inc.php');
}
if(!class_exists('Minify_CSS_Compressor')){
	include_once(dirname(__FILE__).DS.'lib'.DS.'Compressor.php');
}

class CSSedit extends Gdn_Plugin {

	public function Base_Render_Before($Sender) {
		if (IsMobile() && !C('Plugins.CSSedit.AddOnMobile'))
			return;
		if (C('Plugins.CSSedit.Stylesheet')) {
			$Sender->AddCssFile('cache/CSSedit/'.C('Plugins.CSSedit.Stylesheet'));
		}
	}

	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = $Sender->EventArguments['SideMenu'];
		$Menu->AddLink('Appearance', T('CSSedit'), 'settings/cssedit', 'Garden.Settings.Manage');
	}

	public function SettingsController_CSSedit_Create($Sender, $Args){
		$Sender->Permission('Garden.Settings.Manage');
		$StyleSheetPath = PATH_UPLOADS.DS.'CSSedit'.DS;
		$StyleSheet = $StyleSheetPath.'source.css';
		$Source = '';
		if (!file_exists($StyleSheetPath))
			mkdir($StyleSheetPath, 0777, TRUE);
		//This is for compatibility with v0.1 and will be removed in 1.0
		$OldSheet = PATH_CACHE.DS.'CSSedit'.DS.'source.css';
		if (!file_exists($StyleSheet) && file_exists($OldSheet)) {
			rename($OldSheet, $StyleSheet);
		}
		if($Sender->Form->IsPostBack()){
			$FormValues = $Sender->Form->FormValues();
			$Source = GetValue('Style', $FormValues);
			$Preprocessor = GetValue('Preprocessor', $FormValues);
			file_put_contents($StyleSheet, $Source);
			file_put_contents($StyleSheetPath.time().'.css', $Source);
			$this->limitRevisions();
			SaveToConfig('Plugins.CSSedit.Preprocessor', $Preprocessor);
			SaveToConfig('Plugins.CSSedit.AddOnMobile', GetValue('AddOnMobile', $FormValues));
			if ($this->makeCSS($Sender, $Source, $Preprocessor, time())) {
				$Sender->InformMessage('<span class="InformSprite Check"></span> '
						.T('Your changes were saved.', 'HasSprite'));
			} else {
				$Sender->InformMessage('<span class="InformSprite Bug"></span> '
						.T('Compilation Error: Please check your LESS'), 'Dismissable HasSprite');
			}
		} else {
			$Sender->Form->SetValue('Preprocessor', C('Plugins.CSSedit.Preprocessor'));
			$Sender->Form->SetValue('AddOnMobile', C('Plugins.CSSedit.AddOnMobile'));
			if (file_exists($StyleSheet)) {
				$Source = file_get_contents($StyleSheet);
			}
		}
		$Sender->Form->SetValue('Style', $Source);
		$Sender->AddSideMenu('settings/cssedit');
		$Sender->SetData('Title', T('CSS Editor'));
		$Sender->AddJsFile('ace.js','plugins/CSSedit/js/ace-min-noconflict');
		$Sender->AddJsFile('cssedit.js','plugins/CSSedit');
		$Sender->Render($this->GetView('cssedit.php'));
	}

	protected function limitRevisions() {
		$revs = glob(PATH_UPLOADS.DS.'CSSedit'.DS.'*.css');
		$revcount = count($revs);
		for ($i = 0; $revcount - $i > 21; $i++) {
			if (basename($revs[$i]) == 'source.css')
				continue;
			unlink($revs[$i]);
		}
	}

	protected function makeCSS($Sender, $String, $Preprocessor, $Token) {
		$Filename = $Token.'.css';
		$CachePath = PATH_CACHE.DS.'CSSedit'.DS;
		$FullPath = $CachePath.$Filename;
		if (!file_exists($CachePath))
			mkdir($CachePath, 0777, TRUE);
		if ($Preprocessor == 1) {
			$less = new lessc;
			try {
				$String = $less->compile($String);
			} catch (exception $e) {
				$Sender->InformMessage($e->getMessage(), 'Dismissable');
				return false;
			}
		}
		file_put_contents($FullPath, Minify_CSS_Compressor::process($String));
		if (C('Plugins.CSSedit.Stylesheet'))
			unlink($CachePath.C('Plugins.CSSedit.Stylesheet'));
		SaveToConfig('Plugins.CSSedit.Stylesheet', $Filename);
		return true;
	}
}
