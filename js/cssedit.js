jQuery(function() {
	var editor = ace.edit('AceEditor');
	var leave = false;
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
		leave = true;
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
	jQuery('.CSSrevision').click(function(e) {
		e.preventDefault();
		var time = jQuery(this).text();
		jQuery.get(jQuery(this).attr('href'), function(data) {
			if (confirm('Load ' + time +
				' revision?\nAll unsaved changes will be lost.')) {
				editor.setValue(data);
			}
		});
	});
	jQuery(window).on('beforeunload', function() {
		if (editor.getValue() != css && !leave) {
			return 'Do you really want to leave? Your changes will be lost.';
		}
	}); 
});