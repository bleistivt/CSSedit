/*jslint browser: true, this: true */
/*global jQuery, window, ace, gdn*/

jQuery(function ($) {
    'use strict';

    var editor = ace.edit('AceEditor'),
        leave = false,
        initmode = $("#Form_Preprocessor option:selected").val(),
        css = document.getElementById('Form_Style').value,
        scroll,
        setMode = function (mode) {
            if (mode === '1') {
                editor.getSession().setMode('ace/mode/less');
            } else if (mode === '2') {
                editor.getSession().setMode('ace/mode/scss');
            } else {
                editor.getSession().setMode('ace/mode/css');
            }
        };

    setMode(initmode);
    editor.$blockScrolling = Infinity;
    editor.setTheme('ace/theme/crimson_editor');
    editor.setValue(css, -1);
    editor.focus();

    if (localStorage.getItem('scrollposition')) {
        try {
            scroll = JSON.parse(localStorage.getItem('scrollposition'));
            editor.moveCursorToPosition(scroll.pos);
            editor.getSession().setScrollTop(scroll.scroll);
        } catch (ignore) {}
    }

    $('#AceEditor').show();
    $('#NoJsForm').hide();
    $('.CSSeditPrev').show();

    $('.CSSeditSave, .CSSeditPrev').on('click', function (e) {
        e.preventDefault();
        leave = true;
        $('#Form_Style').val(editor.getValue());
        if ($(this).is('.CSSeditPrev')) {
            $('#PreviewToggle').val(true);
        }
        localStorage.setItem('scrollposition', JSON.stringify({
            pos: editor.getCursorPosition(),
            scroll: editor.getSession().getScrollTop()
        }));
        $('#Form_CSSedit').submit();
    });

    $('#Form_Preprocessor').change(function () {
        setMode($("#Form_Preprocessor option:selected").val());
    });

    $('.CSSrevision').click(function (e) {
        e.preventDefault();
        var time = $(this).text();
        $.get($(this).attr('href'), function (data) {
            var confirm = window.confirm(gdn.definition('CSSedit.loadMessage').replace('%s', time));
            if (confirm) {
                editor.setValue(data);
            }
        });
    });

    $(window).on('beforeunload', function () {
        if ((editor.getValue() !== css || gdn.definition('CSSedit.confirmLeave', false)) && !leave) {
            return gdn.definition('CSSedit.leaveMessage');
        }
    });

});
