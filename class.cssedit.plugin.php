<?php

$PluginInfo['CSSedit'] = array(
    'Name' => 'CSSedit',
    'Description' => 'Adds a CSS (LESS/SCSS) style editor to the Dashboard.',
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'SettingsUrl' => '/dashboard/settings/cssedit',
    'License' => 'GNU GPL2',
    'MobileFriendly' => true
);

class CSSeditPlugin extends Gdn_Plugin {

    // Adds the stylesheet to every page except the dashboard.
    public function Base_Render_Before($Sender) {
        if (!in_array($Sender->MasterView, array('', 'default'))) {
            return;
        }
        if (IsMobile() && !C('Plugins.CSSedit.AddOnMobile')) {
            return;
        }

        if ($Preview = Gdn::Session()->Stash('CSSeditPreview', '', false)) {
            // Preview a stylesheet
            $Sender->AddCssFile('cache/CSSedit/'.$Preview);
            $Sender->InformMessage(
                Wrap('', 'span',
                    array(
                        'class' => 'InformSprite',
                        'style' => 'background:url('
                                   .SmartAsset('/plugins/CSSedit/icon.png')
                                   .') no-repeat;background-size:100%;margin:15px 0 0 1px;'
                    )
                )
                .Wrap(T('You are looking at a preview of your changes.'), 'p')
                .Wrap(Anchor(T('Return to the editor'), 'dashboard/settings/cssedit'), 'p'),
                'HasSprite'
            );

        } elseif (C('Plugins.CSSedit.Stylesheet')) {
            // Finally, add the actual stylesheet to the page.
            $Sender->AddCssFile('cache/CSSedit/'.C('Plugins.CSSedit.Stylesheet'));
        }
    }


    // Adds the CSSedit Link to the Dashboard.
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Appearance', T('CSS Editor'), 'settings/cssedit', 'Garden.Settings.Manage');
    }


    // The editor page
    public function SettingsController_CSSedit_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Session = Gdn::Session();

        // Check if the preview button was toggled.
        $Preview = val('Preview', $Sender->Form->FormValues(), false);
        $StyleSheetPath = PATH_UPLOADS.'/CSSedit/';
        $StyleSheet = ($Preview) ? $StyleSheetPath.'preview.css' : $StyleSheetPath.'source.css';
        $Source = '';

        if ($Sender->Form->AuthenticatedPostBack()) {
            // Process a form submission.
            $FormValues = $Sender->Form->FormValues();
            $Source = val('Style', $FormValues, '');
            $Preprocessor = val('Preprocessor', $FormValues, 0);

            // Write files to the stylesheet directory.
            if (!file_exists($StyleSheetPath)) {
                mkdir($StyleSheetPath, 0755, true);
            }
            // Save as revision if we are not in preview mode.
            if (!$Preview) {
                file_put_contents($StyleSheet, $Source);
                file_put_contents($StyleSheetPath.time().'.css', $Source);
                $this->CleanUp();
            }

            // Save the config values.
            SaveToConfig('Plugins.CSSedit.Preprocessor', $Preprocessor);
            SaveToConfig('Plugins.CSSedit.AddOnMobile', val('AddOnMobile', $FormValues));

            // Try to build the stylesheet.
            if ($this->makeCSS($Sender, $Source, $Preprocessor, $Preview)) {
                $Sender->InformMessage(
                    '<span class="InformSprite Check"></span> '.T('Your changes have been saved.'),
                    'HasSprite'
                );
            } else {
                $Sender->InformMessage(
                    '<span class="InformSprite Bug"></span> '.T('Compilation Error:'),
                    'Dismissable HasSprite'
                );
                $Preview = false;
            }

        } else {
            // Prepare the form.
            $Sender->Form->SetValue('Preprocessor', C('Plugins.CSSedit.Preprocessor'));
            $Sender->Form->SetValue('AddOnMobile', C('Plugins.CSSedit.AddOnMobile'));
            Gdn::Session()->Stash('CSSeditPreview');
            //Get the uncompressed source from the Session Stash.
            $Preview = Gdn::Session()->Stash('CSSeditPreviewSource');

            if ($Preview) {
                $Source = file_get_contents($Preview);
                $Preview = false;
            } elseif (file_exists($StyleSheet)) {
                $Source = file_get_contents($StyleSheet);
            }
        }

        if ($Preview) {
            Redirect('/');
        }

        //Render the editor page
        $Sender->Form->SetValue('Style', $Source);
        $Sender->SetData('Title', T('CSS Editor'));

        $Sender->AddSideMenu('settings/cssedit');
        $Sender->AddJsFile('ace.js', 'plugins/CSSedit/js/ace-min-noconflict');
        $Sender->AddJsFile('cssedit.js', 'plugins/CSSedit');

        $Sender->Render('cssedit', '', 'plugins/CSSedit');
    }

    // Download the style as a theme.
    public function SettingsController_CSSexport_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        // $ThemeInfo array
        $Default = array(
            'Name' => T('Untitled'),
            'Version' => '1.0',
            'Author' => Gdn::Session()->User->Name,
            'Description' => T('Created with CSSedit plugin, ').Gdn_Format::Date()
        );

        if ($Sender->Form->AuthenticatedPostBack()) {
            $FormValues = $Sender->Form->FormValues();
            array_map('trim', $FormValues);

            foreach ($Default as $Key => &$Val) {
                $Val = val($Key, $FormValues, $Val);
            }
            $Slug = Gdn_Format::Url($Default['Name']);

            // Write the contents of the about.php file.
            $About = "<?php\n\n"
                .'$ThemeInfo[\''.$Slug.'\'] = '.var_export($Default, true).";\n";

            $Zip = new ZipArchive;
            $File = tempnam(sys_get_temp_dir(), 'css');

            if ($Zip->open($File, ZIPARCHIVE::OVERWRITE) === true) {

                $Stylesheet = C('Plugins.CSSedit.Stylesheet');
                if ($Stylesheet) {

                    // Add the stylesheet to the archive.
                    if (!C('Plugins.CSSedit.Preprocessor')) {
                        $Zip->addFile(PATH_UPLOADS.'/CSSedit/source.css', $Slug.'/design/custom.css');
                    } else {
                        // Add the source as a text file if a preprocessor is used.
                        $Zip->addFile(PATH_CACHE.'/CSSedit/'.$Stylesheet, $Slug.'/design/custom.css');
                        $Zip->addFile(PATH_UPLOADS.'/CSSedit/source.css', $Slug.'/design/source.txt');
                    }
                    // Add the theme info file.
                    $Zip->addFromString($Slug.'/about.php', $About);
                    $Zip->close();

                    // Serve the file.
                    ob_end_clean();
                    // Controller->Header() only works when the page is rendered.
                    header('Content-Type: application/zip');
                    header('Content-Length: '.filesize($File));
                    header('Content-Disposition: attachment; filename="'.$Slug.'.zip"');
                    readfile($File);
                    unlink($File);
                    exit();

                } else {
                    $Sender->Form->AddError(T('No stylesheet found.'));
                }

            } else {
                $Sender->Form->AddError(T('Couldn\'t create zip file.'));
            }
        }
        $Sender->Form->SetData($Default);

        $Sender->AddSideMenu();
        $Sender->SetData('Title', T('Export as theme'));
        $Sender->Render('export', '', 'plugins/CSSedit');
    }

    protected function CleanUp() {
        // Only keep the last 25 revisions in the stylesheet directory.
        $Revisions = glob(PATH_UPLOADS.'/CSSedit/*.css');
        $RevisionCount = count($Revisions);
        for ($i = 0; $RevisionCount - $i > 26; $i++) {
            if (basename($Revisions[$i]) == 'source.css') {
                continue;
            }
            unlink($Revisions[$i]);
        }

        //Remove cached previews
        $CachePath = PATH_CACHE.'/CSSedit/';
        foreach (glob(PATH_CACHE.'/CSSedit/*preview.css') as $Preview) {
            unlink($Preview);
        }
        if (file_exists($CachePath.'previewsrc.css')) {
            unlink($CachePath.'previewsrc.css');
        }
    }


    // Compile and minify the stylesheet.
    // Returns true on success and false on failure.
    protected function makeCSS($Sender, $String, $Preprocessor, $Preview) {
        // Use the creation timestamp as filename.
        $Token = time();
        $Filename = $Token.'.css';
        $CachePath = PATH_CACHE.'/CSSedit/';
        $FullPath = $CachePath.$Filename;
        if (!file_exists($CachePath)) {
            mkdir($CachePath, 0755, true);
        }

        // Save uncompressed source when in preview mode.
        if ($Preview) {
            file_put_contents($CachePath.'previewsrc.css', $String);
            Gdn::Session()->Stash('CSSeditPreviewSource', $CachePath.'previewsrc.css');
        }

        if ($Preprocessor == 1) {
            // Compile LESS
            $less = new lessc;
            try {
                $String = $less->compile($String);
            } catch (exception $e) {
                // Send the error message to the editor page.
                $Sender->InformMessage($e->getMessage(), 'Dismissable');
                return false;
            }
        } elseif ($Preprocessor == 2) {
            // Compile SCSS
            $scss = new scssc();
            try {
                $String = $scss->compile($String);
            } catch (exception $e) {
                $Sender->InformMessage($e->getMessage(), 'Dismissable');
                return false;
            }
        }

        // Minify and save the stylesheet.
        $String = Minify_CSS_Compressor::process($String);

        if ($Preview) {
            file_put_contents($CachePath.$Token.'preview.css', $String);
            Gdn::Session()->Stash('CSSeditPreview', $Token.'preview.css');
        } else {
            file_put_contents($FullPath, $String);
            $OldStyleSheet = C('Plugins.CSSedit.Stylesheet');
            if ($OldStyleSheet && file_exists($CachePath.$OldStyleSheet)) {
                unlink($CachePath.$OldStyleSheet);
            }
            SaveToConfig('Plugins.CSSedit.Stylesheet', $Filename);
        }
        return true;
    }

}
