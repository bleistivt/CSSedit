<?php if (!defined('APPLICATION')) exit();

echo wrap($this->data('Title'), 'h1');

if ($this->data('revisions')) {
    echo '<div class="Help Aside" style="border-top:none;"><h2>', t('Revisions'), '</h2><ul>';
    foreach ($this->data('revisions') as $time => $url) {
        echo wrap(anchor(Gdn_Format::dateFull($time), $url, 'CSSrevision'), 'li');
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
    echo $this->Form->open(array('id' => 'Form_CSSedit'));
    echo $this->Form->errors();
    ?>
    <ul>
        <li id="NoJsForm">
        <?php echo $this->Form->textBox('Style', array('MultiLine' => true, 'class' => 'InputBox WideInput')); ?>
        </li>
        <li>
        <?php echo $this->Form->dropDown('Preprocessor', array(0 => 'CSS', 1 => 'LESS', 2 => 'SCSS')); ?>
        <div style="display:inline-block;">
            <?php echo $this->Form->checkBox('AddOnMobile', 'Enable on mobile theme'); ?>
        </div>
        </li>
        <li>
          <?php if ($this->Form->getValue('Style') && c('Garden.Theme') == 'default') {
                    // taget="_blank" until there is a way to download a file from a popup.
                    echo anchor(t('Export as theme'), '/settings/cssexport', array('target' => '_blank'));
                } ?>
        </li>
        <li>
        <?php echo $this->Form->hidden('Preview', array('id' => 'PreviewToggle', 'value' => false));
              echo $this->Form->button('Preview', array('class' => 'Button CSSeditPrev', 'style' => 'display:none;'));
              echo $this->Form->button('Save', array('class' => 'Button CSSeditSave')); ?>
        </li>
    </ul>
    <?php echo $this->Form->close(); ?>
    </div>
</div>
