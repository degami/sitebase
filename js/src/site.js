//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/moment/min/moment-with-locales.min.js
//=include ../../node_modules/is-in-viewport/lib/isInViewport.min.js
//=include ../../node_modules/jquery-lazy/jquery.lazy.min.js
//=include ../../node_modules/jquery.cycle2/src/jquery.cycle2.min.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js
//=include ../../node_modules/gallery/lib/gallery.js
//=include ../i18n.js

(function($){
    $(document).ready(function(){
        $('img[data-src]').Lazy();

        $('.uncachable-block').each(function(){
            var $this = $(this);
            if (!$this.hasClass('processed')) {
                $.ajax('/crud/uncachableblock', {
                    method: 'POST',
                    cache: false,
                    contentType: 'application/json',
                    data: JSON.stringify($this.data('uncachable')),
                }).done(function(data) {
                    $this.html(data.html);
                    $this.addClass('processed');
                });
            }
        });

        cookieStore.get('darkmode').then(function(data){
            $('#darkmode-selector').prop('checked', data?.value);
        });
        $('#darkmode-selector').change(function(evt) {
            if ($(this).prop('checked')) {
                $.cookie('darkmode', $(this).prop('checked') ? true : null, { expires: 365, path: '/' });
                $('body').addClass('dark-mode');
            } else {
                $.removeCookie('darkmode', {path: '/'});
                $('body').removeClass('dark-mode');
            }
        });
    });
})(jQuery);