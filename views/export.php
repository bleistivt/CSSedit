<?php if (!defined('APPLICATION')) exit();

echo wrap($this->title(), 'h1');

echo $this->Form->open();
echo $this->Form->errors();
?>

<ul>
  <li>
    <div class="Info"><?php echo t('This packages your stylesheet so that it can be installed like a regular theme. <strong>If you have made changes, save before using this.</strong>'); ?></p>
  </li>
  <li>
    <?php echo $this->Form->label('Name')
        .$this->Form->textBox('Name'); ?>
  </li>
  <li>
    <?php echo $this->Form->label('Version')
        .$this->Form->textBox('Version', ['style' => 'width: 50px;']); ?>
  </li>
  <li>
    <?php echo $this->Form->label('Author')
        .$this->Form->textBox('Author'); ?>
  </li>
  <li>
    <?php echo $this->Form->label('Description')
        .$this->Form->textBox('Description', ['MultiLine' => true]); ?>
  </li>
</ul>
<?php echo $this->Form->close('Export'); ?>
