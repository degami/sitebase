//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js

(function($){
    $.fn.appAdmin = function (methodOrOptions) {
        if ( $.fn.appAdmin.methods[methodOrOptions] ) {
            return $.fn.appAdmin.methods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return $.fn.appAdmin.methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.appAdmin' );
        } 
    };
    $.fn.appAdmin.methods = {
        $elem: null,
        settings: null,

        init : function(options) {
            var instance = this;
            // Iterate and reformat each matched element.
            return this.each(function() {
                var settings = $.extend({}, $.fn.appAdmin.defaults, options);
                var $elem = $( this );

                instance.settings = settings;
                instance.$elem = $elem;

                $elem.data('appAdmin', instance);

                $('select', $elem).select2({'width':'100%'});
                $('a.inToolSidePanel[href]', $elem).click(function(evt){
                    if($(this).attr('href') != '#') {
                       evt.preventDefault(); 
                       $elem.appAdmin('loadPanelContent', $(this).attr('title') || '', $(this).attr('href'), true, true);
                    }
                });
                $('#toolsSidePanel .closebtn', $elem).click(function(evt){
                    $elem.appAdmin('closeSidePanel');
                });

                $('#sidebar-minimize-btn').on('click', function() {
                    if ($('.sidebar').hasClass('collapsed')) {
                        $.removeCookie('sidebar_size');
                    } else {
                        $.cookie('sidebar_size','minimized');
                    }
                    $('.sidebar').toggleClass('collapsed');
                });

                if ($.cookie('sidebar_size') == 'minimized') {
                    $('.sidebar').addClass('collapsed');
                }

                $('#sidebarCollapse').on('click', function () {
                    if ($('.sidebar').hasClass('active')) {
                        $elem.appAdmin('hideOverlay');
                    } else {
                        $elem.appAdmin('showOverlay');
                    }
                    $('.sidebar', $elem).toggleClass('active');
                });
                $('.sidebar .closebtn', $elem).click(function(evt){
                    $elem.appAdmin('hideOverlay');
                    $('.sidebar', $elem).removeClass('active');
                });

                $elem.appAdmin('checkLoggedStatus');

                $('#search-btn').click(function(evt){
                    evt.preventDefault();
                    $elem.appAdmin('searchTableColumns', this);
                })
            });
        },
        showOverlay: function() {
            $('#overlay').addClass('d-block').removeClass('d-none');
        },
        hideOverlay: function() {
            $('#overlay').addClass('d-none').removeClass('d-block');
        },
        getSettings: function() {
            return $(this).data('appAdmin').settings;
        },
        openSidePanel: function() {
            $(this).appAdmin('showOverlay');            
            $('.sidepanel', this).css({'width': '95%'});
        },
        closeSidePanel: function() {
            $('.sidepanel', this).data('lastLoadedUrl', false);
            $(this).appAdmin('hideOverlay');
            $('.sidepanel', this).css({'width': 0});
        },
        getElem: function() {
            return $(this).data('appAdmin').$elem;
        },
        loadPanelContent: function(title, url, open_panel, store_last_url) {
            var that = this;
            if (undefined == open_panel) {
                open_panel = true;
            }
            if (undefined == store_last_url || open_panel == true) {
                store_last_url = true;
            }

            $.getJSON(url, function (response){
                if (store_last_url) {
                    $('.sidepanel', that).data('lastLoadedUrl', url);                    
                }
                $('.sidepanel', that).find('.card-title').html(title);
                $('.sidepanel', that).find('.card-block').html(response.html || '');

                // add behaviours
                $('.sidepanel select:not(".select2")', that).select2({
                    dropdownCssClass: "in_sidepanel",
                    'width':'100%'
                });
                $('.sidepanel form', that).submit(function(evt){
                    evt.preventDefault();
                    var formData = new FormData(this);
                    $.ajax({
                        type: "POST",
                        url: $(this).attr("action"),
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            $(that).appAdmin('reloadPanelContent');
                        },
                        error: function() {
                            alert("Error");
                        }
                    });
                    return false;
                });

                if( $.trim(response.js) != '' ){ 
                    eval(response.js); 
                };
                if (open_panel) {
                    $(that).appAdmin('openSidePanel');                    
                }
                $('.sidepanel', that).find('.card-block a[href]').click(function(evt){
                    if($(this).attr('href') != '#') {
                       evt.preventDefault(); 
                       $(that).appAdmin('loadPanelContent', title, $(this).attr('href'), false, false);
                    }
                });
            });
        },
        reloadPanelContent: function() {
            var url = $('.sidepanel', this).data('lastLoadedUrl');
            $(this).appAdmin('loadPanelContent', $('.sidepanel', this).find('.card-title').text(), url, false);
        },
        checkLoggedStatus: function() {
            var that = this;

            var checkUrl = $(this).appAdmin('getSettings').checkLoggedUrl;
            var logoutUrl = $(this).appAdmin('getSettings').logoutUrl;

            $.ajax({
                type: "GET",
                url: checkUrl,
                data: null,
                processData: false,
                contentType: 'application/json',
                success: function(data) {
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    if (xhr.status == 403) {
                        document.location = logoutUrl;
                    }
                }
            });

            window.setTimeout(function(){
                $(that).appAdmin('checkLoggedStatus');
            }, 60000);
        },
        searchTableColumns: function(btn) {
            var query = $('input', $(btn).data('target')).serialize();
            var href = $(btn).attr('href');
            document.location = href + (href.indexOf('?') != -1 ? '&' : '?') + query;
        },
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.appAdmin.defaults = {
        'checkLoggedUrl': null,
        'logoutUrl': null,
    }
})(jQuery);  
