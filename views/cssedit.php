<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data['Title']; ?></h1>
<?php
$StyleRevisions = array_reverse(glob(PATH_UPLOADS.DS.'CSSedit'.DS.'*.css'));
if (count($StyleRevisions) > 1 && method_exists('Gdn_Format', 'DateFull')) {
	echo '<div class="Help Aside" style="border-top:none;"><h2>', T('Revisions'), '</h2><ul>';
	foreach ($StyleRevisions as $rev) {
		if (basename($rev) == 'source.css')
			continue;
		echo Wrap(Anchor(Gdn_Format::DateFull(basename($rev, '.css')), '/uploads/CSSedit/'.basename($rev), 'CSSrevision'), 'li');
	}
	echo '</ul></div>';
}   
?>
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
