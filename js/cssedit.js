jQuery(function() {
	var editor = ace.edit('AceEditor');
	editor.setTheme('ace/theme/crimson_editor');
	if (jQuery("#Form_Preprocessor option:selected").val() == '1') {
		editor.getSession().setMode('ace/mode/less');
	} else {
		editor.getSession().setMode('ace/mode/css');
	}
	var css = document.getElementById('Form_Style').value;
	editor.setValue(css);
	jQuery('#AceEditor').show();
	jQuery('#NoJsForm').hide();
	jQuery('.CSSeditSave').on('click', function(e) {
		e.preventDefault();
		jQuery('#Form_Style').val(editor.getValue());
		jQuery('#Form_CSSedit').submit();
	});
	jQuery('#Form_Preprocessor').change(function() {
		var selectboxvalue = jQuery("#Form_Preprocessor option:selected").val();
		if (selectboxvalue == '1') {
			editor.getSession().setMode('ace/mode/less');
		} else {
			editor.getSession().setMode('ace/mode/css');
		}
	});
});