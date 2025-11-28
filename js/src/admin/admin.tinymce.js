const $ = require('jquery');

if (window.tinymce && typeof tinymce.on === 'function') {
    tinymce.on('AddEditor', function(e) {
        const ed = e && (e.editor || e);
        if (!ed || !ed.id) return;

        const $ta = $('#' + ed.id);
        if ($ta.length && $ta.data('initiallyDisabled')) {
            if (ed.mode && typeof ed.mode.set === 'function') {
                ed.mode.set('readonly');
            } else if (typeof ed.setMode === 'function') {
                ed.setMode('readonly');
            } else if (ed.options && typeof ed.options.set === 'function') {
                ed.options.set('disabled', true);
            }
        }
    });
}
