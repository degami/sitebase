//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/moment/min/moment-with-locales.min.js
//=include ../../node_modules/is-in-viewport/lib/isInViewport.min.js
//=include ../../node_modules/jquery-lazy/jquery.lazy.min.js
//=include ../../node_modules/jquery.cycle2/src/jquery.cycle2.min.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js
//=include ../../node_modules/gallery/lib/gallery.js

(function($){
    $(document).ready(function(){
        $('img[data-src]').Lazy();
    });
})(jQuery);