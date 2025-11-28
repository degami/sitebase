const $ = require('jquery');

window.appAdminMethodsAlert = {
    showAlertDialog: function(options) {
        const defaults = {
            title: __('Alert'),
            message: '',
            type: 'info', // info | success | warning | danger
            okText: 'OK',
            cancelText: null,
            onConfirm: null,
            onCancel: null
        };
        const settings = $.extend({}, defaults, options);

        // Rimuove eventuali dialog precedenti
        $('#appAdminAlertDialog').remove();

        // Scelta classe bootstrap in base al tipo
        let alertClass = 'primary';
        switch (settings.type) {
            case 'success': alertClass = 'success'; break;
            case 'warning': alertClass = 'warning'; break;
            case 'danger':  alertClass = 'danger'; break;
            case 'info': 
            default: alertClass = 'info'; break;
        }

        // Costruzione dialog
        const dialogHtml = `
            <div id="appAdminAlertDialog" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-${alertClass}">
                <div class="modal-header bg-${alertClass} text-white">
                    <h5 class="modal-title">${settings.title}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>${settings.message}</p>
                </div>
                <div class="modal-footer">
                    ${settings.cancelText ? `<button type="button" class="btn btn-secondary" id="alertCancelBtn">${settings.cancelText}</button>` : ''}
                    <button type="button" class="btn btn-${alertClass}" id="alertOkBtn">${settings.okText}</button>
                </div>
                </div>
            </div>
            </div>
        `;

        $('body').append(dialogHtml);

        const $dialog = $('#appAdminAlertDialog');

        $dialog.modal({ backdrop: 'static', keyboard: true }).modal('show');

        $('#alertOkBtn', $dialog).on('click', function() {
            $dialog.modal('hide');
            if (typeof settings.onConfirm === 'function') settings.onConfirm();
        });
        $('#alertCancelBtn', $dialog).on('click', function() {
            $dialog.modal('hide');
            if (typeof settings.onCancel === 'function') settings.onCancel();
        });

        // Cleanup dopo chiusura
        $dialog.on('hidden.bs.modal', function () {
            $dialog.remove();
        });
    },
    notify: function(text, callback) {
        let that = this;

        $(that).appAdmin('showAlertDialog', {
            type: 'info',
            title: __('Notice'),
            message: text,
            onConfirm: callback
        });
    },
    info: function(text, callback) {
        let that = this;
        
        $(that).appAdmin('showAlertDialog', {
            type: 'info',
            title: __('Info'),
            message: text,
            onConfirm: callback
        });
    },
    warning: function(text, callback) {
        let that = this;

        $(that).appAdmin('showAlertDialog', {
            type: 'warning',
            title: __('Warning'),
            message: text,
            onConfirm: callback
        });
    },
    success: function(text, callback) {
        let that = this;

        $(that).appAdmin('showAlertDialog', {
            type: 'success',
            title: __('Success'),
            message: text,
            onConfirm: callback
        });
    },
    error: function(text, callback) {
        let that = this;

        $(that).appAdmin('showAlertDialog', {
            type: 'danger',
            title: __('Error'),
            message: text,
            onConfirm: callback
        });
    },
};
