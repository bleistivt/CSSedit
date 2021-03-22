/*global jQuery, window, ace, gdn*/

jQuery(($) => {
    ace.config.set('workerPath', '//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/');
    const editor = ace.edit('AceEditor');
    let leave = false;
    const css = document.getElementById('Form_Style').value;
    let scroll;

    editor.session.setMode('ace/mode/css');
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

    $('.CSSrevision').click(function (e) {
        e.preventDefault();
        const time = $(this).text();
        $.get($(this).attr('href'), (data) => {
            const confirm = window.confirm(gdn.definition('CSSedit.loadMessage').replace('%s', time));
            if (confirm) {
                editor.setValue(data);
            }
        });
    });

    $(window).on('beforeunload', () => {
        if ((editor.getValue() !== css || gdn.definition('CSSedit.confirmLeave', false)) && !leave) {
            return gdn.definition('CSSedit.leaveMessage');
        }
    });
});
