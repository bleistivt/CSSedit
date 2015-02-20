<?php if (!defined('APPLICATION')) exit();

echo Wrap($this->Data('Title'), 'h1');

$StyleRevisions = array_reverse(glob(PATH_UPLOADS.'/CSSedit/*.css'));
if (count($StyleRevisions) > 1) {
    echo '<div class="Help Aside" style="border-top:none;"><h2>', T('Revisions'), '</h2><ul>';
    foreach ($StyleRevisions as $rev) {
        if (basename($rev) == 'source.css') {
            continue;
        }
        $revtime = Gdn_Format::DateFull(basename($rev, '.css'));
        echo Wrap(Anchor($revtime, '/uploads/CSSedit/'.basename($rev), 'CSSrevision'), 'li');
    }
    echo '</ul></div>';
}
?>
<div class="Configuration">
   <div class="ConfigurationForm">
    <ul>
        <li>
            <div id="AceEditor" style="height:550px;border-bottom:1px solid #82bddd;display:none;"></div>
        </li>
    </ul>
    <?php
    echo $this->Form->Open(array('id' => 'Form_CSSedit'));
    echo $this->Form->Errors();
    ?>
    <ul>
        <li id="NoJsForm">
        <?php echo $this->Form->TextBox('Style', array('MultiLine' => true, 'class' => 'InputBox WideInput')); ?>
        </li>
        <li>
        <?php echo $this->Form->DropDown('Preprocessor', array(0 => 'CSS', 1 => 'LESS', 2 => 'SCSS')); ?>
        <div style="display:inline-block;">
            <?php echo $this->Form->CheckBox('AddOnMobile', 'Enable on mobile theme'); ?>
        </div>
        </li>
        <li>
        <?php echo $this->Form->Hidden('Preview', array('id' => 'PreviewToggle', 'value' => false));
              echo $this->Form->Button('Preview', array('class' => 'Button CSSeditPrev', 'style' => 'display:none;'));
              echo $this->Form->Button('Save', array('class' => 'Button CSSeditSave')); ?>
        </li>
    </ul>
    <?php echo $this->Form->Close(); ?>
    </div>
</div>
