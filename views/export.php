<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>
<?php echo $this->Form->Open().$this->Form->Errors();?>
<ul>
  <li>
    <div class="Info"><?php echo T('This packages your stylesheet so that it can be installed like a regular theme. <strong>If you have made changes, save before using this.</strong>'); ?></p>
  </li>
  <li>
    <?php echo $this->Form->Label('Name')
        .$this->Form->TextBox('Name'); ?>
  </li>
  <li>
    <?php echo $this->Form->Label('Version')
        .$this->Form->TextBox('Version', array('style' => 'width: 50px;')); ?>
  </li>
  <li>
    <?php echo $this->Form->Label('Author')
        .$this->Form->TextBox('Author'); ?>
  </li>
  <li>
    <?php echo $this->Form->Label('Description')
        .$this->Form->TextBox('Description', array('MultiLine' => true)); ?>
  </li>
</ul>
<?php echo $this->Form->Close('Export'); ?>
