<?php

$PluginInfo['CSSedit'] = array(
    'Name' => 'CSSedit',
    'Description' => 'Adds a CSS (LESS/SCSS) style editor to the Dashboard.',
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'SettingsUrl' => 'settings/cssedit',
    'License' => 'GNU GPL2',
    'MobileFriendly' => true
);

class CSSeditPlugin extends Gdn_Plugin {

    // Adds the stylesheet to every page except the dashboard.
    public function base_render_before($sender) {
        if (!in_array($sender->MasterView, array('', 'default'))) {
            return;
        }
        if (isMobile() && !c('Plugins.CSSedit.AddOnMobile')) {
            return;
        }

        if ($preview = Gdn::session()->stash('CSSeditPreview', '', false)) {
            // Preview a stylesheet
            $sender->addCssFile('cache/CSSedit/'.$preview);
            $sender->informMessage(
                wrap('', 'span',
                    array(
                        'class' => 'InformSprite',
                        'style' => 'background:url('
                                   .smartAsset('/plugins/CSSedit/icon.png')
                                   .') no-repeat;background-size:100%;margin:15px 0 0 1px;'
                    )
                )
                .wrap(t('You are looking at a preview of your changes.'), 'p')
                .wrap(anchor(t('Return to the editor'), 'settings/cssedit'), 'p'),
                'HasSprite'
            );

        } elseif (c('Plugins.CSSedit.Stylesheet')) {
            // Finally, add the actual stylesheet to the page.
            $sender->addCssFile('cache/CSSedit/'.c('Plugins.CSSedit.Stylesheet'));
        }
    }


    // Adds the CSSedit Link to the Dashboard.
    public function base_getAppSettingsMenuItems_handler($sender, &$args) {
        $args['SideMenu']->addLink('Appearance', t('CSS Editor'), 'settings/cssedit', 'Garden.Settings.Manage');
    }


    // The editor page
    public function settingsController_cssEdit_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        // Check if the preview button was toggled.
        $preview = val('Preview', $sender->Form->formValues(), false);
        $styleSheetPath = PATH_UPLOADS.'/CSSedit/';
        $styleSheet = ($preview) ? $styleSheetPath.'preview.css' : $styleSheetPath.'source.css';
        $source = '';

        if ($sender->Form->authenticatedPostBack()) {
            // Process a form submission.
            $formValues = $sender->Form->formValues();
            $source = val('Style', $formValues, '');
            $preprocessor = val('Preprocessor', $formValues, 0);

            // Write files to the stylesheet directory.
            if (!file_exists($styleSheetPath)) {
                mkdir($styleSheetPath, 0755, true);
            }
            // Save as revision if we are not in preview mode.
            if (!$preview) {
                file_put_contents($styleSheet, $source);
                file_put_contents($styleSheetPath.time().'.css', $source);
                $this->cleanUp();
            }

            // Save the config values.
            saveToConfig('Plugins.CSSedit.Preprocessor', $preprocessor);
            saveToConfig('Plugins.CSSedit.AddOnMobile', val('AddOnMobile', $formValues));

            // Try to build the stylesheet.
            if ($this->makeCSS($sender, $source, $preprocessor, $preview)) {
                $sender->informMessage(
                    '<span class="InformSprite Check"></span> '.t('Your changes have been saved.'),
                    'HasSprite'
                );
            } else {
                $sender->informMessage(
                    '<span class="InformSprite Bug"></span> '.t('Compilation Error:'),
                    'Dismissable HasSprite'
                );
                $preview = false;
            }

        } else {
            // Prepare the form.
            $sender->Form->setValue('Preprocessor', c('Plugins.CSSedit.Preprocessor'));
            $sender->Form->setValue('AddOnMobile', c('Plugins.CSSedit.AddOnMobile'));
            Gdn::session()->stash('CSSeditPreview');
            //Get the uncompressed source from the Session Stash.
            $preview = Gdn::session()->stash('CSSeditPreviewSource');

            if ($preview) {
                $source = file_get_contents($preview);
                $preview = false;
            } elseif (file_exists($styleSheet)) {
                $source = file_get_contents($styleSheet);
            }
        }

        if ($preview) {
            redirect('/');
        }

        //Render the editor page
        $sender->Form->setValue('Style', $source);
        $sender->title(t('CSS Editor'));

        $sender->addSideMenu('settings/cssedit');
        $sender->addJsFile('ace.js', 'plugins/CSSedit/js/ace-min-noconflict');
        $sender->addJsFile('cssedit.js', 'plugins/CSSedit');

        $sender->render('cssedit', '', 'plugins/CSSedit');
    }

    // Download the style as a theme.
    public function settingsController_cssExport_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        // $ThemeInfo array
        $default = array(
            'Name' => t('Untitled'),
            'Version' => '1.0',
            'Author' => Gdn::session()->User->Name,
            'Description' => t('Created with CSSedit plugin, ').Gdn_Format::date()
        );

        if ($sender->Form->authenticatedPostBack()) {
            $formValues = $sender->Form->formValues();
            array_map('trim', $formValues);

            foreach ($default as $key => &$val) {
                $val = val($key, $formValues, $val);
            }
            $slug = Gdn_Format::url($default['Name']);

            // Write the contents of the about.php file.
            $about = "<?php\n\n"
                .'$ThemeInfo[\''.$slug.'\'] = '.var_export($default, true).";\n";

            $zip = new ZipArchive;
            $file = tempnam(sys_get_temp_dir(), 'css');

            if ($zip->open($file, ZIPARCHIVE::OVERWRITE) === true) {

                $stylesheet = c('Plugins.CSSedit.Stylesheet');
                if ($stylesheet) {

                    // Add the stylesheet to the archive.
                    if (!c('Plugins.CSSedit.Preprocessor')) {
                        $zip->addFile(PATH_UPLOADS.'/CSSedit/source.css', $slug.'/design/custom.css');
                    } else {
                        // Add the source as a text file if a preprocessor is used.
                        $zip->addFile(PATH_CACHE.'/CSSedit/'.$stylesheet, $slug.'/design/custom.css');
                        $zip->addFile(PATH_UPLOADS.'/CSSedit/source.css', $slug.'/design/source.txt');
                    }
                    // Add the theme info file.
                    $zip->addFromString($slug.'/about.php', $about);
                    $zip->close();

                    // Serve the file.
                    ob_end_clean();
                    // Controller->Header() only works when the page is rendered.
                    header('Content-Type: application/zip');
                    header('Content-Length: '.filesize($file));
                    header('Content-Disposition: attachment; filename="'.$slug.'.zip"');
                    readfile($file);
                    unlink($file);
                    exit();

                } else {
                    $sender->Form->addError(t('No stylesheet found.'));
                }

            } else {
                $sender->Form->addError(t('Couldn\'t create zip file.'));
            }
        }
        $sender->Form->setData($default);

        $sender->addSideMenu();
        $sender->title(t('Export as theme'));
        $sender->render('export', '', 'plugins/CSSedit');
    }

    protected function cleanUp() {
        // Only keep the last 25 revisions in the stylesheet directory.
        $revisions = glob(PATH_UPLOADS.'/CSSedit/*.css');
        for ($i = 0, $count = count($revisions); $count - $i > 26; $i++) {
            if (basename($revisions[$i]) != 'source.css') {
                unlink($revisions[$i]);
            }
        }

        //Remove cached previews
        $cachePath = PATH_CACHE.'/CSSedit/';
        foreach (glob($cachePath.'*preview.css') as $preview) {
            unlink($preview);
        }
        if (file_exists($cachePath.'previewsrc.css')) {
            unlink($cachePath.'previewsrc.css');
        }
    }


    // Compile and minify the stylesheet.
    // Returns true on success and false on failure.
    protected function makeCSS($sender, $string, $preprocessor, $preview) {
        // Use the creation timestamp as filename.
        $token = time();
        $filename = $token.'.css';
        $cachePath = PATH_CACHE.'/CSSedit/';
        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        // Save uncompressed source when in preview mode.
        if ($preview) {
            file_put_contents($cachePath.'previewsrc.css', $string);
            Gdn::session()->stash('CSSeditPreviewSource', $cachePath.'previewsrc.css');
        }

        if ($preprocessor == 1) {
            // Compile LESS
            $less = new lessc;
            try {
                $string = $less->compile($string);
            } catch (exception $e) {
                // Send the error message to the editor page.
                $sender->informMessage($e->getMessage(), 'Dismissable');
                return false;
            }
        } elseif ($preprocessor == 2) {
            // Compile SCSS
            $scss = new scssc();
            try {
                $string = $scss->compile($string);
            } catch (exception $e) {
                $sender->informMessage($e->getMessage(), 'Dismissable');
                return false;
            }
        }

        // Minify and save the stylesheet.
        $string = Minify_CSS_Compressor::process($string);

        if ($preview) {
            file_put_contents($cachePath.$token.'preview.css', $string);
            Gdn::session()->stash('CSSeditPreview', $token.'preview.css');
        } else {
            file_put_contents($cachePath.$filename, $string);
            $oldStyleSheet = c('Plugins.CSSedit.Stylesheet');
            if ($oldStyleSheet && file_exists($cachePath.$oldStyleSheet)) {
                unlink($cachePath.$oldStyleSheet);
            }
            saveToConfig('Plugins.CSSedit.Stylesheet', $filename);
        }
        return true;
    }

}
