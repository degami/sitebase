const $ = require('jquery');

window.appAdminMethodsBase = {
    $elem: null,
    settings: null,
    notificationTimeout: null,
    sessionTimeout: null,

    showOverlay: function(namespace) {
        let overlayId = '#overlay';
        if (namespace) {
            overlayId = '#' + namespace + '-overlay';
        }
        if ($(overlayId).length == 0) {
            $('body').append('<div id="'+namespace+'-overlay" class="overlay d-none"></div>');
        }
        $(overlayId).addClass('d-block').removeClass('d-none');
    },
    hideOverlay: function(namespace) {
        let overlayId = '#overlay';
        if (namespace) {
            overlayId = '#' + namespace + '-overlay';
        }
        if ($(overlayId).length == 0) {
            $('body').append('<div id="'+namespace+'-overlay" class="overlay d-none"></div>');
        }

        $(overlayId).addClass('d-none').removeClass('d-block');
    },
    getSettings: function() {
        return $(this).data('appAdmin').settings || $(this).data('appAdmin').defaults;
    },
    getElem: function() {
        return $(this).data('appAdmin').$elem;
    },
    fullPageLoader: function(add = true) {
        let that = this;
        if ($('#fullPageLoader').length > 0) {
            $('#fullPageLoader').remove();
        }
        if (add) {
            $('body').append('<div id="fullPageLoader" class="overlay d-flex align-items-center align-content-center"><div class="m-auto d-flex flex-column align-items-center align-content-center"><span class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status"></span><span class="sr-only">Loading...</span></div></div>');
        }
    },
    logout: function () {
        let that = this;
        if (that.notificationTimeout != null) {
            window.clearTimeout(that.notificationTimeout);
        }
        if (that.sessionTimeout != null) {
            window.clearTimeout(that.sessionTimeout);
        }
    },
    copyToClipboard: function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "absolute";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.select();
            return new Promise((res, rej) => {
                document.execCommand('copy') ? res() : rej();
                textArea.remove();
            });
        }
    },
    showTooltip: function(element, message) {
        $('.simple-tooltip').remove();

        let $element = $(element);
        const offset = $element.offset();
        const tooltip = $('<div class="simple-tooltip"></div>').text(message).appendTo('body');

        tooltip.css({
            top: offset.top - tooltip.outerHeight() - 10,
            left: offset.left + $element.outerWidth() / 2 - tooltip.outerWidth() / 2
        });

        tooltip.addClass('show');

        setTimeout(() => {
            tooltip.removeClass('show');
            setTimeout(() => tooltip.remove(), 200);
        }, 1500);
    },
    show : function( ) {    },
    hide : function( ) {  },
    update : function( content ) {  }
};
