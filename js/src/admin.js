//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js
//=include ../../node_modules/highlightjs/highlight.pack.js
//=include ../../node_modules/moment/min/moment-with-locales.min.js
//=include ./tinymce-plugin-block.js

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

        notificationTimeout: null,
        sessionTimeout: null,

        init : function(options) {
            let instance = this;
            // Iterate and reformat each matched element.
            return this.each(function() {
                let settings = $.extend({}, $.fn.appAdmin.defaults, options);
                let $elem = $( this );

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

                $('#darkmode-selector').on('change', function () {
                    $elem.appAdmin('updateUserUiSettings', 
                        {'darkMode': $(this).is(':checked')},
                        function (data) {
                            //document.location.reload();
                            $('body').toggleClass('dark-mode');
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
                    $('.navbar-brand').toggleClass('collapsed');
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

                $('#logout-btn').click(function(evt){
                    $elem.appAdmin('logout');
                });

                $('#search-btn').click(function(evt){
                    evt.preventDefault();
                    $elem.appAdmin('searchTableColumns', this);
                })
            
                if ($elem.appAdmin('getSettings').aiAvailable) {
                    document.addEventListener('keydown', function(e) {
                        if (e.ctrlKey && e.shiftKey && e.code === 'KeyC') {
                            e.preventDefault();
                            if (!$('.sideChat').hasClass('open')) {
                                $elem.appAdmin('openAIChat');
                            } else {
                                $elem.appAdmin('closeAIChat');
                            }
                        }
                    });
                }

                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.shiftKey && e.code === 'KeyM') { 
                        e.preventDefault();
                        $('#darkmode-selector').prop('checked', !$('#darkmode-selector').prop('checked')).trigger('change');
                    }
                });

            });
        },
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
            return $(this).data('appAdmin').settings;
        },
        openSidePanel: function() {
            let that = this;

            $(this).appAdmin('showOverlay');
            $('.sidepanel', this).css({'width': '95%'});

            $(that).data('sidePanelEscUnbind', $(that).appAdmin('createEscHandler', function() {
                $(that).appAdmin('closeSidePanel');
            }, 'sidePanel'));
        },
        closeSidePanel: function() {
            let that = this;

            $('.sidepanel', this).data('lastLoadedUrl', false);
            $(this).appAdmin('hideOverlay');
            $('.sidepanel', this).css({'width': 0});

            const unbindEsc = $(this).data('sidePanelEscUnbind');
            if (unbindEsc) {
                unbindEsc();
            }
        },
        openAIChat: function() {
            let that = this;

            if (!$(that).appAdmin('getSettings').aiAvailable || $('.sideChat', that).length == 0) {
                return;
            }

            $(this).appAdmin('showOverlay');

            let chatPanelWidth = '350px';
            $(that).appAdmin('getUserUiSettings', function(data) {
                if (undefined != data.chatAIpanelWidth) {
                    chatPanelWidth = data.chatAIpanelWidth;
                }

                $('.sideChat', that).css({'width': chatPanelWidth}).addClass('open');
            });

            $('.sideChat', that).css({'width': chatPanelWidth}).addClass('open');


            $('.sideChat:not(".ai-processed")', this).each(function(index, chatPanel){
                $('.closebtn', $(chatPanel)).click(function(evt){
                    $(that).appAdmin('closeAIChat');
                });

                $('#chatSendBtn', $(chatPanel)).off('click').on('click', function(e) {
                    e.preventDefault();

                    let callbackFunc = function(data) {
                        if (data.success == false) {
                            console.log('Error: ' + data.error);
                            return;
                        }
 
                        let text = data.text;
                        let prompt = data.prompt;
                        let messageId = data.messageId;

                        let messageContent = '<div class="chat-message"><div class="item me"><strong>Me:</strong> ' + prompt + '</div><div class="item"><strong>AI:</strong> ' + text + '</div>';
                        if (null != messageId) {
                            $('#chatMessages').find('#'+messageId).replaceWith(messageContent);
                        } else {
                            $('#chatMessages').append(messageContent);
                        }
                        $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
                    }

                    let text = $('#chatInput').val();
                    if (text.trim() != '') {
                        $('#chatInput').val('');
                        let messageId = 'message_' + moment(new Date()).unix();

                        $('#chatMessages').append('<div id="'+messageId+'" class="d-flex p-5 justify-content-center"><div class="loader" /></div>');

                        switch ($('#chatAISelector').val()) {
                            case 'chatGPT':
                                $(that).appAdmin('askChatGPT', {'prompt': text, 'messageId': messageId}, callbackFunc);
                                break;
                            case 'gemini':
                                $(that).appAdmin('askGoogleGemini', {'prompt': text, 'messageId': messageId}, callbackFunc);
                                break;
                        }
                    }
                });

                $('#chatInput', $(chatPanel)).off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        $('#chatSendBtn').trigger('click');
                    }
                });
                
                $('#chatAISelector', $(chatPanel)).off('change').on('change', function(e) {
                    $(that).appAdmin('updateUserUiSettings', 
                        {'preferredAI': $(this).val()},
                        function (data) {
                        }
                    );
                });

                $(that).appAdmin('getUserUiSettings', function(data) {
                    if (undefined != data.preferredAI) {
                        $('#chatAISelector', $(chatPanel)).val(data.preferredAI);
                    }
                });

                const resizer = $('#chatSidebarResizer', $(chatPanel))[0];
                let isResizing = false;

                resizer.addEventListener('mousedown', function (e) {
                    isResizing = true;
                    document.body.style.cursor = 'ew-resize';
                });

                document.addEventListener('mousemove', function (e) {
                    if (!isResizing) return;
                    const newWidth = window.innerWidth - e.clientX;
                    if (newWidth > 250 && newWidth < 800) { // limiti min/max
                        $(chatPanel).css({'width': newWidth + 'px'});
                    }
                });

                document.addEventListener('mouseup', function () {
                    if (isResizing) {
                        isResizing = false;

                        $(that).appAdmin('updateUserUiSettings', 
                            {'chatAIpanelWidth': $(chatPanel).css('width')},
                            function (data) {
                            }
                        );

                        document.body.style.cursor = '';
                    }
                });

            }).addClass('ai-processed');    
            
            $(that).data('aiChatEscUnbind', $(that).appAdmin('createEscHandler', function() {
                $(that).appAdmin('closeAIChat');
            }, 'aiChat'));
        },
        closeAIChat: function() {
            let that = this;

            $(this).appAdmin('hideOverlay');
            $('.sideChat', this).css({'width': 0}).removeClass('open');

            const unbindEsc = $(this).data('aiChatEscUnbind');
            if (unbindEsc) {
                unbindEsc();
            }
        },
        createEscHandler: function(callback, namespace) {
            const handler = function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    callback();
                }
            };

            // Usa un namespace per evitare conflitti
            const namespacedEvent = 'keydown.appAdmin.' + namespace;

            $(document).on(namespacedEvent, handler);

            // Restituisci una funzione per rimuovere il listener
            return function() {
                $(document).off(namespacedEvent, handler);
            };
        },
        getElem: function() {
            return $(this).data('appAdmin').$elem;
        },
        loadPanelContent: function(title, url, open_panel, store_last_url) {
            let that = this;
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
                $('.sidepanel', that).find('.card').css({'height': '95%'});
                $('.sidepanel', that).find('.card-block').css({'height': '100%'});
                
                // add behaviours
                $('.sidepanel select:not(".select-processed")', that).select2({
                    dropdownCssClass: "in_sidepanel",
                    'width':'100%'
                }).addClass('select-processed');
                $('.sidepanel form', that).submit(function(evt){
                    evt.preventDefault();
                    let formData = new FormData(this);
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
            let url = $('.sidepanel', this).data('lastLoadedUrl');
            $(this).appAdmin('loadPanelContent', $('.sidepanel', this).find('.card-title').text(), url, false);
        },
        checkLoggedStatus: function() {
            let that = this;

            let checkUrl = $(this).appAdmin('getSettings').checkLoggedUrl;
            let logoutUrl = $(this).appAdmin('getSettings').logoutUrl;

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

            that.sessionTimeout = window.setTimeout(function(){
                $(that).appAdmin('checkLoggedStatus');
            }, 60000);
        },
        searchTableColumns: function(btn) {
            let query = $('input, select, textarea', $(btn).data('target')).serialize();
            let href = $(btn).attr('href');
            document.location = href + (href.indexOf('?') != -1 ? '&' : '?') + query;
        },
        askAI: function(type, params, targetOrCallback) {
            let AIUrl = null;
            switch(type) {
                case 'chatGPT':
                    AIUrl = $(this).appAdmin('getSettings').chatGPTUrl;
                    break;
                case 'gemini':
                    AIUrl = $(this).appAdmin('getSettings').googleGeminiUrl;
                    break;    
            }

            if (undefined != params.messageId) {
                AIUrl += '?messageId='+params.messageId;
            }

            if (AIUrl != null) {
                $.ajax({
                    type: "POST",
                    url: AIUrl,
                    data: JSON.stringify({'prompt': params.prompt}),
                    processData: false,
                    contentType: 'application/json',
                    success: function(data) {
                        if (data.success == true) {
                            if (typeof targetOrCallback === 'function') {
                                targetOrCallback(data);
                            } else {
                                let $target = $(targetOrCallback);
                                if ($target.is('input')) {
                                    $target.val(data.text);
                                } else if ($target.is('div,p,span,li,textarea')) {
                                    $target.html(data.text);
                                    if (tinymce.get($target.attr('id'))) {
                                        tinymce.get($target.attr('id')).setContent(data.text);
                                    }
                                }
                            }
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        if (typeof targetOrCallback === 'function') {
                            targetOrCallback({ success: false, error: thrownError });
                        }
                    }
                });
            }
        },
        askChatGPT: function(params, targetOrCallback) {
            $(this).appAdmin('askAI', 'chatGPT', params, targetOrCallback);
        },
        askGoogleGemini: function(params, targetOrCallback) {
            $(this).appAdmin('askAI', 'gemini', params, targetOrCallback);
        },
        updateUserUiSettings: function(settings, succesCallback) {
            let that = this;
            let uIsettingsUrl = $(this).appAdmin('getSettings').uIsettingsUrl;
            $.ajax({
                type: "POST",
                url: uIsettingsUrl,
                data: JSON.stringify(settings),
                processData: false,
                contentType: 'application/json',
                success: function( data, textStatus, xhr) {
                    $(that).data('userSettings', data.settings);
                    succesCallback(data.settings);
                },
                error: function(xhr, ajaxOptions, thrownError) {}
            });
        },
        getUserUiSettings: function(succesCallback) {
            let that = this;
            if ($(that).data('userSettings') != null) {
                succesCallback(that.data('userSettings'));
                return;
            }

            let uIsettingsUrl = $(this).appAdmin('getSettings').uIsettingsUrl;
            $.ajax({
                type: "GET",
                url: uIsettingsUrl,
                processData: false,
                contentType: 'application/json',
                success: function( data, textStatus, xhr) {
                    $(that).data('userSettings', data.settings);
                    succesCallback(data.settings);
                },
                error: function(xhr, ajaxOptions, thrownError) {}
            });
        },
        fetchNotifications: function() {
            let that = this;
        
            let notificationsUrl = $(this).appAdmin('getSettings').notificationsUrl;
            let notificationDismissUrl = $(this).appAdmin('getSettings').notificationCrudUrl;

            $.ajax({
                type: "GET",
                url: notificationsUrl,
                data: null,
                processData: false,
                contentType: 'application/json',
                success: function(data) {
                    if (Array.isArray(data.notifications) && data.notifications.length > 0) {
                        data.notifications.forEach(function(notification, index) {
                            let notificationId = 'notificationDialog_' + index;
        
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
                            let dialogId = $(this).data('dialogid');
                            let notificationId = $(this).data('id');                            
        
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

            that.notificationTimeout = window.setTimeout(function() {
                $(that).appAdmin('fetchNotifications');
            }, 30000);
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
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.appAdmin.defaults = {
        'rootUrl': null,
        'checkLoggedUrl': null,
        'logoutUrl': null,
        'chatGPTUrl': null,
        'googleGeminiUrl': null,
        'uIsettingsUrl': null,
        'currentRoute': null,
        'notificationsUrl': null,
        'notificationCrudUrl': null,
        'aiAvailable': null,
    }
})(jQuery);
