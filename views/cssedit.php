<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data['Title']; ?></h1>
<div class="Configuration">
   <div class="ConfigurationForm">
   
	<ul>
		<li>
			<div id="AceEditor" style="height:500px;display:none;"></div>
		</li>
	</ul>
	<?php
    echo $this->Form->Open(array('id' => 'Form_CSSedit'));
	echo $this->Form->Errors();
	?>
    <ul>
		<li id="NoJsForm">
		<?php echo $this->Form->TextBox('Style', array('MultiLine' => TRUE, 'class' => 'InputBox WideInput')); ?>
		</li>
		<li>
		<?php echo $this->Form->DropDown('Preprocessor', array(0 => 'CSS', 1 => 'LESS')); ?>
		<div style="display:inline-block;">
			<?php echo $this->Form->CheckBox('AddOnMobile', T('Also add declarations to mobile style')); ?>
		</div>
		</li>
		<li>
		<?php echo $this->Form->Button(T('Save'), array('class' => 'Button CSSeditSave')); ?>
		</li>
	</ul>
	<?php echo $this->Form->Close(); ?>
	</div>
</div>
