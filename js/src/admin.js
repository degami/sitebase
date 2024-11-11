//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js
//=include ../../node_modules/highlightjs/highlight.pack.js
//=include ../../node_modules/moment/min/moment-with-locales.min.js

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

                $('select:not(".select-processed")', $elem).each(function(index, select){
                    $(select).select2({'width': $(select).css('width') ? $(select).css('width') : '100%'}).addClass('select-processed');
                });

                $('select.paginator-items-choice').on('change', function(){
                    $elem.appAdmin('updateUserUiSettings', 
                        {'currentRoute': $elem.appAdmin('getSettings').currentRoute, 'itemsPerPage': $(this).val()},
                        function (data) {
                            document.location.reload();
                        }
                    );
                });

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
                        //$.removeCookie('sidebar_size');
                        $elem.appAdmin('updateUserUiSettings', 
                            {'sidebar_size': ''},
                            function (data) { }
                        );
                    } else {
                        //$.cookie('sidebar_size','minimized');
                        $elem.appAdmin('updateUserUiSettings', 
                            {'sidebar_size': 'minimized'},
                            function (data) { }
                        );
                    }
                    $('.sidebar').toggleClass('collapsed');
                });

                $elem.appAdmin('getUserUiSettings', function(data) {
                    if (data.sidebar_size == 'minimized') {
                        $('.sidebar').addClass('collapsed');                        
                    }
                });


                if ($.cookie('sidebar_size') == 'minimized') {
                    //$('.sidebar').addClass('collapsed');
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

                // no need to fetch notifications in notification page
                if ($elem.appAdmin('getSettings').currentRoute != 'admin.usernotifications') {
                    $elem.appAdmin('fetchNotifications');
                }

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
                $('.sidepanel select:not(".select-processed")', that).select2({
                    dropdownCssClass: "in_sidepanel",
                    'width':'100%'
                }).addClass('select-processed');
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
            var query = $('input, select, textarea', $(btn).data('target')).serialize();
            var href = $(btn).attr('href');
            document.location = href + (href.indexOf('?') != -1 ? '&' : '?') + query;
        },
        askAI: function(type, text, target) {
            var AIUrl = null;
            switch(type) {
                case 'chatGPT':
                    AIUrl = $(this).appAdmin('getSettings').chatGPTUrl;
                    break;
                case 'gemini':
                    AIUrl = $(this).appAdmin('getSettings').googleGeminiUrl;
                    break;    
            }

            if (AIUrl != null) {
                $.ajax({
                    type: "POST",
                    url: AIUrl,
                    data: JSON.stringify({'prompt': text}),
                    processData: false,
                    contentType: 'application/json',
                    success: function(data) {
                        if (data.success == true) {
                            var $target = $(target);
                            if ($target.is('input')) {
                                $target.val(data.text);
                            } else if ($target.is('div,p,span,li,textarea')) {
                                $target.html(data.text);
                            }
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {}
                });
            }
        },
        askChatGPT: function(text, target) {
            $(this).appAdmin('askAI', 'chatGPT', text, target);
        },
        askGoogleGemini: function(text, target) {
            $(this).appAdmin('askAI', 'gemini', text, target);
        },
        updateUserUiSettings: function(settings, succesCallback) {
            var uIsettingsUrl = $(this).appAdmin('getSettings').uIsettingsUrl;
            $.ajax({
                type: "POST",
                url: uIsettingsUrl,
                data: JSON.stringify(settings),
                processData: false,
                contentType: 'application/json',
                success: succesCallback,
                error: function(xhr, ajaxOptions, thrownError) {}
            });
        },
        getUserUiSettings: function(succesCallback) {
            var uIsettingsUrl = $(this).appAdmin('getSettings').uIsettingsUrl;
            $.ajax({
                type: "GET",
                url: uIsettingsUrl,
                processData: false,
                contentType: 'application/json',
                success: succesCallback,
                error: function(xhr, ajaxOptions, thrownError) {}
            });
        },
        fetchNotifications: function() {
            var that = this;
        
            var notificationsUrl = $(this).appAdmin('getSettings').notificationsUrl;
            var notificationDismissUrl = $(this).appAdmin('getSettings').notificationCrudUrl;

            $.ajax({
                type: "GET",
                url: notificationsUrl,
                data: null,
                processData: false,
                contentType: 'application/json',
                success: function(data) {
                    if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                        data.notifications.forEach(function(notification, index) {
                            var notificationId = 'notificationDialog_' + index;
        
                            if ($('#' + notificationId).length === 0) {
                                $('body').append(`
                                    <div id="${notificationId}" class="position-fixed" style="display: none;bottom: ${20 + index * 50}px; right: 20px; z-index: 1050; max-width: 300px;">
                                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                                            <span>${notification.sender}</span>: <span>${notification.message}</span>
                                            <button type="button" class="close closeNotification" data-dialogid="${notificationId}" data-id="${notification.id}" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    </div>
                                `);
                                $('#' + notificationId).fadeIn();
                            }
                        });
        
                        $('body').on('click', '.closeNotification', function() {
                            var dialogId = $(this).data('dialogid');
                            var notificationId = $(this).data('id');                            
        
                            $.ajax({
                                type: "PUT",
                                url: notificationDismissUrl.replace('{id:\\d+}', notificationId),
                                data: JSON.stringify({ id: notificationId, read: true, read_at: moment(new Date()).format('YYYY-MM-DD HH:mm:ss') }),
                                contentType: 'application/json',
                                success: function(response) {
                                    console.log(response, "we can remove #"+dialogId);
                                    $('#' + dialogId).fadeOut(function(){
                                        $(this).remove();
                                    });
                                },
                                error: function(xhr) {
                                    console.error('Errore durante la chiusura della notifica:', dialogId);
                                }
                            });
                        });
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    console.error('Errore AJAX:', thrownError);
                }
            });

            window.setTimeout(function() {
                $(that).appAdmin('fetchNotifications');
            }, 30000);
        },
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.appAdmin.defaults = {
        'checkLoggedUrl': null,
        'logoutUrl': null,
        'chatGPTUrl': null,
        'googleGeminiUrl': null,
        'uIsettingsUrl': null,
        'currentRoute': null,
        'notificationsUrl': null,
        'notificationCrudUrl': null,
    }
})(jQuery);
