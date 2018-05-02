<?php if (!defined('APPLICATION')) exit();

echo heading($this->title());

if ($this->data('revisions')) {
    $revisions = '<ul>';
    foreach ($this->data('revisions') as $time => $url) {
        $revisions .= wrap(anchor(Gdn_Format::dateFull($time), $url, 'CSSrevision'), 'li');
    }
    $revisions .= '</ul>';
    helpAsset(t('Revisions'), $revisions);
}
?>

<div id="AceEditor" style="height:550px;display:none;margin:0 -1.125rem;border-bottom:0.0625rem solid #e7e8e9;"></div>

<?php
echo $this->Form->open(['id' => 'Form_CSSedit']);
echo $this->Form->errors();
?>
<ul>
    <li class="form-group" id="NoJsForm">
    <?php echo $this->Form->textBox('Style', [
        'MultiLine' => true,
        'class' => 'InputBox WideInput'
    ]); ?>
    </li>
    <li class="form-group">
        <?php echo $this->Form->toggle('Mobile', 'Enable on mobile theme'); ?>
    </li>
    <?php if ($this->Form->getValue('Style') && c('Garden.Theme') == 'default') {
        // taget="_blank" until there is a way to download a file from a popup.
        echo wrap(
            anchor(t('Export as theme'), '/settings/cssexport', ['target' => '_blank', 'class' => 'btn']),
            'li',
            ['class' => 'form-group']
        );
    } ?>
</ul>
<div class="form-footer">
<?php echo $this->Form->hidden('Preview', ['id' => 'PreviewToggle', 'value' => false]);
      echo $this->Form->button('Preview', [
        'class' => 'Button CSSeditPrev',
        'style' => 'display:none;'
      ]);
      echo $this->Form->button('Save', ['class' => 'Button CSSeditSave']); ?>
</div>

<?php echo $this->Form->close(); ?>
