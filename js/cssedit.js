jQuery(function($) {
	var editor = ace.edit('AceEditor');
	var leave = false;
	editor.setTheme('ace/theme/crimson_editor');
	var initmode = $("#Form_Preprocessor option:selected").val();
	if (initmode == '1') {
		editor.getSession().setMode('ace/mode/less');
	} else if (initmode == '2') {
		editor.getSession().setMode('ace/mode/scss');
	} else {
		editor.getSession().setMode('ace/mode/css');
	}
	var css = document.getElementById('Form_Style').value;
	editor.setValue(css, -1);
	editor.focus();
	if (localStorage.getItem('scrollposition')) {
		try {
			editor.moveCursorToPosition(JSON.parse(localStorage.getItem('scrollposition')));
		} catch (e) { }
	}
	$('#AceEditor').show();
	$('#NoJsForm').hide();
	$('.CSSeditPrev').show();
	$('.CSSeditSave, .CSSeditPrev').on('click', function(e) {
		e.preventDefault();
		leave = true;
		$('#Form_Style').val(editor.getValue());
		if ($(this).is('.CSSeditPrev')) {
			$('#PreviewToggle').val(true);
		}
		localStorage.setItem('scrollposition', JSON.stringify(editor.getCursorPosition()));
		$('#Form_CSSedit').submit();
	});
	$('#Form_Preprocessor').change(function() {
		var selectboxvalue = $("#Form_Preprocessor option:selected").val();
		if (selectboxvalue == '1') {
			editor.getSession().setMode('ace/mode/less');
		} else if (selectboxvalue == '2') {
			editor.getSession().setMode('ace/mode/scss');
		} else {
			editor.getSession().setMode('ace/mode/css');
		}
	});
	$('.CSSrevision').click(function(e) {
		e.preventDefault();
		var time = $(this).text();
		$.get($(this).attr('href'), function(data) {
			if (confirm('Load ' + time +
				' revision?\nAll unsaved changes will be lost.')) {
				editor.setValue(data);
			}
		});
	});
	$(window).on('beforeunload', function() {
		if (editor.getValue() != css && !leave) {
			return 'Do you really want to leave? Your changes will be lost.';
		}
	});
});
