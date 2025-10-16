(function (global, $) {
    'use strict';

    var Translations = {};

    function loadTranslations(locale, callback) {
        var url = '/translations/' + locale + '.json';

        $.getJSON(url)
            .done(function (data) {
                Translations = data || {};
                if (callback) callback();
            })
            .fail(function () {
                console.warn('[i18n] Missing translations for locale "' + locale + '", falling back to "en".');
                if (locale !== 'en') {
                    loadTranslations('en', callback);
                } else if (callback) {
                    callback();
                }
            });
    }

    function __(key) {
        var args = Array.prototype.slice.call(arguments, 1);
        var text = Translations[key] || key;
        if (args.length > 0) {
            text = text.replace(/%s/g, function () {
                return args.shift();
            });
        }
        return text;
    }

    // Esporta in globale
    global.loadTranslations = loadTranslations;
    global.__ = __;

})(window, jQuery);