<?php

class CSSeditPlugin extends Gdn_Plugin {

    public function __construct() {
        parent::__construct();
        $this->sourcePath = '/CSSedit/source/';
        $this->cachePath = '/CSSedit/cache/';
        $this->sourceDir = PATH_UPLOADS.$this->sourcePath;
        $this->cacheDir = PATH_UPLOADS.$this->cachePath;
    }


    // Adds the stylesheet to every page except the dashboard.
    public function base_render_before($sender) {
        if ($sender->MasterView == 'admin' || (isMobile() && !c('CSSedit.Mobile'))) {
            return;
        }

        $manage = checkPermission('Garden.Settings.Manage');
        if ($manage && $preview = Gdn::session()->stash('CSSeditPreview', '', false)) {
            // Preview a stylesheet
            $sender->addCssFile(Gdn_Upload::url($this->cachePath.$preview));
            $icon = smartAsset('plugins/CSSedit/icon.png');
            $sender->informMessage(
                wrap('', 'span', [
                    'class' => 'InformSprite',
                    'style' => 'background:url('.$icon.');background-size:100%;margin:7px 1px;'
                ]).t('You are looking at a preview of your changes.').'<br>'
                .anchor(t('Return to the editor'), 'settings/cssedit'),
                'HasSprite'
            );
        } elseif (c('CSSedit.Token')) {
            // Add the actual stylesheet to the page.
            $sender->addCssFile(Gdn_Upload::url($this->cachePath.c('CSSedit.Token').'.css'));
        }
    }


    // Adds the CSSedit link to the Dashboard.
    public function base_getAppSettingsMenuItems_handler($sender, $args) {
        $args['SideMenu']->addLink('Appearance', t('CSS Editor'), 'settings/cssedit', 'Garden.Settings.Manage');
    }


    // The editor page
    public function settingsController_cssEdit_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        // Check if the preview button was toggled.
        $preview = $sender->Form->getFormValue('Preview');
        $source = '';

        if ($sender->Form->authenticatedPostBack()) {
            // Save the config values.
            saveToConfig('CSSedit.Mobile', $sender->Form->getValue('Mobile'));

            // Try to save the stylesheet.
            try {
                $this->save($sender->Form->getValue('Style', ''), $preview);
                $sender->informMessage(sprite('Check', 'InformSprite').t('Your changes have been saved.'), 'HasSprite');
            } catch (Exception $e) {
                $message = t('Compilation Error:').'<br>'.$e->getMessage();
                $sender->informMessage(sprite('Bug', 'InformSprite').$message, 'Dismissable HasSprite');
                // Don't preview anything if an error has occurred.
                $preview = false;
            }

        } else {
            // Prepare the form.
            $sender->Form->setValue('Mobile', c('CSSedit.Mobile'));
            Gdn::session()->stash('CSSeditPreview');

            if ($preview = Gdn::session()->stash('CSSeditPreviewSource')) {
                $source = Gdn_FileSystem::getContents($preview);
                // End the preview if the user goes back to the editor.
                $preview = false;
                $sender->addDefinition('CSSedit.confirmLeave', true);
            } else {
                $source = Gdn_FileSystem::getContents($this->stylesheet(true));
            }
        }

        if ($preview) {
            redirectTo('/');
        }

        // Render the editor page
        $sender->Form->setValue('Style', $source);
        $sender->title(t('CSS Editor'));
        $sender->setData('revisions', $this->revisions());
        $sender->setHighlightRoute('settings/cssedit');
        $sender->cssedit = true;

        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js');
        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/mode-css.js');
        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/worker-css.js');
        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/theme-crimson_editor.js');
        $sender->addJsFile('cssedit.js', 'plugins/CSSedit');

        $sender->addDefinition('CSSedit.loadMessage', t("Load %s revision?\nAll unsaved changes will be lost."));
        $sender->addDefinition('CSSedit.leaveMessage', t('Do you really want to leave? Your changes will be lost.'));

        $sender->render('cssedit', '', 'plugins/CSSedit');
    }


    // Download the style as a theme.
    public function settingsController_cssExport_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setHighlightRoute();
        $sender->title(t('Export as theme'));
        $sender->setData(
            'Description',
            t('This packages your stylesheet so that it can be installed like a regular theme. <strong>If you have made changes, save before using this.</strong>')
        );

        // $ThemeInfo array
        $default = [
            'name' => t('Untitled'),
            'version' => '1.0',
            'authors' => Gdn::session()->User->Name,
            'description' => t('Created using CSSedit plugin, ').Gdn_Format::date()
        ];

        if ($sender->Form->authenticatedPostBack()) {
            $post = array_filter(array_map('trim', $sender->Form->formValues()));
            // Merge with the defaults.
            $default = array_intersect_key($post, $default) + $default;
            $slug = Gdn_Format::url($default['name']);

            // Build addon.json
            $addon = $default;
            $addon['key'] = $slug;
            $addon['type'] = 'theme';
            $addon['authors'] = [['name' => $addon['authors']]];

            $zip = new ZipArchive();
            $file = tempnam(sys_get_temp_dir(), 'cssedit');

            if ($this->stylesheet() && $zip->open($file, ZIPARCHIVE::OVERWRITE) === true) {
                // Add the stylesheet to the archive.
                $zip->addFile($this->stylesheet(true), $slug.'/design/custom.css');
                // Add the theme info file.
                $zip->addFromString($slug.'/addon.json', json_encode($addon, JSON_PRETTY_PRINT));
                $zip->close();

                Gdn_FileSystem::serveFile($file, $slug.'.zip', 'application/zip');

            } elseif (!$this->stylesheet()) {
                $sender->Form->addError(t('No stylesheet found.'));
            } else {
                $sender->Form->addError(t('Couldn\'t create zip file.'));
            }
        }
        $sender->Form->setData($default);

        $conf = new ConfigurationModule($sender);
        $conf->schema([
            'name' => [
                'Control' => 'textbox',
                'LabelCode' => 'Name'
            ],
            'version' => [
                'Control' => 'textbox',
                'LabelCode' => 'Version'
            ],
            'authors' => [
                'Control' => 'textbox',
                'LabelCode' => 'Author'
            ],
            'description' => [
                'Control' => 'textbox',
                'LabelCode' => 'Description',
                'Options' => ['MultiLine' => true]
            ]
        ]);
        $conf->renderAll();
    }


    // Return the path to the stylesheet or its source file.
    private function stylesheet($source = false) {
        if ($source) {
            return $this->sourceDir.'source.css';
        } else if (!c('CSSedit.Token')) {
            return false;
        } else {
            return $this->cacheDir.c('CSSedit.Token').'.css';
        }
    }


    // Minify and save the stylesheet.
    private function save($string, $preview = false) {
        // Use the creation timestamp for the filename.
        $token = time();

        if (!file_exists($this->sourceDir)) {
            mkdir($this->sourceDir, 0755, true);
        }
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        if ($preview) {
            // Save the uncompressed source.
            Gdn_FileSystem::saveFile($this->cacheDir.'previewsrc.css', $string);
            Gdn::session()->stash('CSSeditPreviewSource', $this->cacheDir.'previewsrc.css');

            $string = Minify_CSS_Compressor::process($string);
            Gdn_FileSystem::saveFile($this->cacheDir.$token.'.preview.css', $string);

            // Stash the compiled style for the preview.
            Gdn::session()->stash('CSSeditPreview', $token.'.preview.css');

        } else {
            Gdn_FileSystem::saveFile($this->stylesheet(true), $string);

            // Save a new revision.
            Gdn_FileSystem::saveFile($this->sourceDir.time().'.rev.css', $string);
            $this->clean();

            $string = Minify_CSS_Compressor::process($string);
            Gdn_FileSystem::saveFile($this->cacheDir.$token.'.css', $string);

            // Remove the old file and save the new token to the configuration.
            Gdn_FileSystem::removeFolder($this->stylesheet());
            saveToConfig('CSSedit.Token', $token);
        }
    }


    private function clean() {
        // Only keep the last 25 revisions in the stylesheet directory.
        $revisions = array_reverse(glob($this->sourceDir.'*.rev.css'));
        array_map('unlink', array_slice($revisions, 25));

        // Remove cached previews
        array_map('unlink', glob($this->cacheDir.'*.preview.css'));
        Gdn_FileSystem::removeFolder($this->cacheDir.'previewsrc.css');
    }


    private function revisions() {
        $revisions = [];
        foreach (array_reverse(glob($this->sourceDir.'*.rev.css')) as $rev) {
            $revisions[basename($rev, '.rev.css')] = Gdn_Upload::url($this->sourcePath.basename($rev));
        }
        return $revisions;
    }


    // Migrate old source files.
    public function setup() {
        if (!file_exists($this->sourceDir)) {
            mkdir($this->sourceDir, 0755, true);
        }
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        if ($oldcss = glob(PATH_UPLOADS.'/CSSedit/*.css')) {
            foreach ($oldcss as $css) {
                rename($css, $this->sourceDir.basename($css));
            }
        }
    }

}
