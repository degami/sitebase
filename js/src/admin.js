//=include ../../node_modules/select2/dist/js/select2.full.js
//=include ../../node_modules/jquery.cookie/jquery.cookie.js
//=include ../../node_modules/highlightjs/highlight.pack.js
//=include ../../node_modules/moment/min/moment-with-locales.min.js
//=include ./tinymce-plugin-block.js
//=include ./tinymce-plugin-aitranslate.js

(function($){
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

                $(window).on('beforeunload', function() {
                    $elem.appAdmin('closeSidePanel');
                    $elem.appAdmin('fullPageLoader');
                });

                $(window).on('pageshow', function() {
                    $elem.appAdmin('fullPageLoader', false);
                });

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
                    const $btnElement = $(this);

                    if ($btnElement.attr('href') != '#') {
                        evt.preventDefault();

                        if ($btnElement.hasClass('loading')) {
                            return;
                        }

                        $btnElement.addClass('loading disabled').css('pointer-events', 'none');
                        $('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>').prependTo($btnElement);

                        $elem.appAdmin('loadPanelContent', $(this).attr('title') || '', $(this).attr('href'), true, true, function() {
                            $('.spinner-border', $btnElement).remove();
                            $btnElement.removeClass('loading disabled').css('pointer-events', '');
                        });
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
                });

                $('table[id^="listing-table-"]').each(function(index, table) {
                    $elem.appAdmin('listingTable', table);
                });
            
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
            return $(this).data('appAdmin').settings || $(this).data('appAdmin').defaults;
        },
        openSidePanel: function() {
            let that = this;

            $(that).appAdmin('showOverlay');
            $('.sidepanel', this).css({'width': '95%'});

//            $(that).data('sidePanelEscUnbind', $(that).appAdmin('createEscHandler', function() {
//                $(that).appAdmin('closeSidePanel');
//            }, 'sidePanel'));

            if (!$(that).data('sidePanelUnbinders')) {
                $(that).data('sidePanelUnbinders', []);
            }

            const unbindEsc = $(that).appAdmin('createEscHandler', function() {
                $(that).appAdmin('closeSidePanel');
            }, 'sidePanel');

            const unbinders = $(that).data('sidePanelUnbinders');
            unbinders.push(unbindEsc);
            $(that).data('sidePanelUnbinders', unbinders);            
        },
        closeSidePanel: function() {
            let that = this;

            $('.sidepanel', this).data('lastLoadedUrl', false);
            $(that).appAdmin('hideOverlay');
            $('.sidepanel', that).css({'width': 0});

            $('.sidepanel textarea').each(function() {
                const id = $(this).attr('id');
                if (id && tinymce.get(id)) {
                    tinymce.get(id).remove();
                }
            });

            $('.sidepanel', that).find('.card-title').html('');
            $('.sidepanel', that).find('.card-block').html('');
            $('.sidepanel', that).find('.card').css({'min-height': 'auto'});
            $('.sidepanel', that).find('.card-block').css({'height': 'auto'});

//            const unbindEsc = $(this).data('sidePanelEscUnbind');
//            if (unbindEsc) {
//                unbindEsc();
//            }

            const unbinders = $(this).data('sidePanelUnbinders') || [];
            unbinders.forEach(unbind => {
                if (typeof unbind === 'function') {
                    unbind();
                }
            });
            $(this).removeData('sidePanelUnbinders');
        },
        openAIChat: function() {
            let that = this;

            if (!$(that).appAdmin('getSettings').aiAvailable || $('.sideChat', that).length == 0) {
                return;
            }

            $(that).appAdmin('showOverlay');

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

                        $('#chatMessages').append('<div id="'+messageId+'" class="d-flex p-5 justify-content-center"><div class="spinner-border me-2" style="width: 3rem; height: 3rem;" /></div>');

                        $(that).appAdmin('askAI', $('#chatAISelector').val(), {'prompt': text, 'messageId': messageId}, callbackFunc);
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

            $(that).appAdmin('hideOverlay');
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
        loadPanelContent: function(title, url, open_panel, store_last_url, afterloadCallback = null) {
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
                $('.sidepanel', that).find('.card').css({'min-height': '95%'});
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
                            $(that).appAdmin('showAlertDialog', {
                                title: 'Error',
                                message: 'Generic Error',
                                type: 'error',
                            });
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
                       if ($(this).hasClass('cancel-btn')) {
                            $(that).appAdmin('closeSidePanel');
                            return;
                       }

                       $(that).appAdmin('loadPanelContent', title, $(this).attr('href'), false, false);
                    }
                });
                if (typeof afterloadCallback === 'function') {
                    afterloadCallback();
                }
            });
        },
        reloadPanelContent: function() {
            let that = this;
            let url = $('.sidepanel', this).data('lastLoadedUrl');
            $(that).appAdmin('loadPanelContent', $('.sidepanel', this).find('.card-title').text(), url, false);
        },
        checkLoggedStatus: function() {
            let that = this;

            let checkUrl = $(that).appAdmin('getSettings').checkLoggedUrl;
            let logoutUrl = $(that).appAdmin('getSettings').logoutUrl;

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
        listingTable: function(table) {
            let that = this;

            let $table = $(table);
            let clickTimer = null;
            const delay = 250;

            // initially uncheck every row selector
            $('.table-row-selector, #listing-table-toggle-all', table).prop('checked', false);

            $('#listing-table-toggle-all', table).on('change', function() {
                $(that).appAdmin('listingTableToggleAll', '#'+$table.attr('id'), this);
            });

            $('.table-row-selector', table).on('change', function(evt) {
                let $tr = $(evt.target).closest('tr');
                if ($(this).is(':checked')) {
                    $tr.addClass('selected');
                } else {
                    $tr.removeClass('selected');
                }
            });

            $table.find('tbody tr').on('click', function(evt){
                if ($(evt.target).is('td, tr')) {
                    clearTimeout(clickTimer);

                    let $tr = $(evt.target).closest('tr');

                    clickTimer = setTimeout(function() {                        
                        let identifier = $tr.data('_item_pk');
                        if (undefined !== identifier) {
                            let $checkbox = $('input[type="checkbox"].table-row-selector:eq(0)', $tr);
                            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                        }
                    }, delay);
                }
            }).on('dblclick', function(evt){
                if ($(evt.target).is('td, tr')) {
                    clearTimeout(clickTimer);

                    let $tr = $(evt.target).closest('tr');

                    if ($tr.data('dblclick')) {
                        document.location = $tr.data('dblclick').replace("&amp;","&");
                    }
                }
            });
        },
        listingTableToggleAll: function(tableSelector, element) {
            let $element = $(element);
            let $table = $(tableSelector);
            let $checkboxes = $('input[type="checkbox"].table-row-selector', $table);
            $checkboxes.prop('checked', $element.prop('checked')).trigger('change');
        },
        listingTableGetSelected: function(tableSelector) {
            let $table = $(tableSelector);
            let $checkboxes = $('input[type="checkbox"].table-row-selector:checked', $table);
            return $checkboxes.map(function(index, checkbox) {
                let $checkbox = $(checkbox);
                let identifier = $($checkbox.closest('tr')).data('_item_pk');
                if (undefined !== identifier) {
                    return identifier;
                }
                return null;
            }).get().filter((el) => el);
        },
        listingTableDeleteSelected: function(tableSelector, className) {
            let that = this;
            let identifiers = $(that).appAdmin('listingTableGetSelected', tableSelector);
            if (identifiers.length == 0) {
                $(that).appAdmin('showAlertDialog', {
                    title: 'No elements selected',
                    message: 'Please choose at least one element to delete',
                    type: 'warning',
                });

                return;
            }

            let currentRoute = $(that).appAdmin('getSettings').currentRoute;
            let massDeleteUrl = $(that).appAdmin('getSettings').massDeleteUrl;

            let form = $('<form action="'+massDeleteUrl+'" method="post">').appendTo('body');
            $('<input type="hidden" value="'+encodeURIComponent(currentRoute)+'" name="return_route" />').appendTo(form);
            $('<input type="hidden" value="'+encodeURIComponent(className.replaceAll("\\","\\\\"))+'" name="class_name" />').appendTo(form);
            $.each(identifiers, function(index, identifier){
                const json = encodeURIComponent(JSON.stringify(identifier));
                $('<input type="hidden" value="' + json + '" name="items['+index+']" />').appendTo(form);
            });

//            console.log(form);
            form.submit();
        },
        listingTableEditSelected: function(tableSelector, controllerClassName, modelClassName, btnElement = null) {
            let that = this;
            let identifiers = $(that).appAdmin('listingTableGetSelected', tableSelector);
            if (identifiers.length == 0) {
                $(that).appAdmin('showAlertDialog', {
                    title: 'No elements selected',
                    message: 'Please choose at least one element to edit',
                    type: 'warning',
                });

                return;
            }

            if (btnElement) {
                $('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>').prependTo(btnElement);
            }

            let massEditUrl = $(that).appAdmin('getSettings').massEditUrl +
                '?controller_class_name=' + encodeURIComponent(controllerClassName) +
                '&model_class_name=' + encodeURIComponent(modelClassName);

            $(that).appAdmin('loadPanelContent', 'Mass Edit', massEditUrl, true, true, function() {

                if (btnElement) {
                    $('.spinner-border', btnElement).remove();
                }

                $('.sidepanel form')
                .off('submit')
                .on('submit', function(evt) {
                    evt.preventDefault();
                    if (window.tinymce) tinymce.triggerSave();
                })
                .find('div.form-item')
                .each(function() {
                    const $item = $(this);

                    if ($item.html().trim() === '') return;

                    if ($item.find('> .toggle-enable').length) return;

                    $('<label class="checkbox"><input type="checkbox" class="toggle-enable" /><span class="checkbox__icon"></span></label>').prependTo($item.find('label:eq(0)'));
                    const $checkbox = $item.find('.toggle-enable');

                    $item.find('input:not(\'.toggle-enable\'), select, textarea').each(function() {
                        const $elem = $(this);

                        if ($elem.is('textarea') && !$elem.attr('id')) {
                            $elem.attr('id', 'ta_' + Math.random().toString(36).substr(2, 9));
                        }

                        $elem.prop('disabled', true);
                        $elem.data('initiallyDisabled', true);
                    });

                    function toggleFields(enable) {
                        $item.find('input:not(\'.toggle-enable\'), select').prop('disabled', !enable);

                        $item.find('textarea').each(function() {
                            const $ta = $(this);
                            $ta.prop('disabled', !enable);

                            if (window.tinymce) {
                                const editor = tinymce.get($ta.attr('id'));
                                if (editor) {
                                    if (editor.mode && typeof editor.mode.set === 'function') {
                                        editor.mode.set(enable ? 'design' : 'readonly');
                                    } else if (typeof editor.setMode === 'function') {
                                        editor.setMode(enable ? 'design' : 'readonly');
                                    } else if (editor.options && typeof editor.options.set === 'function') {
                                        editor.options.set('disabled', !enable);
                                    }
                                }
                            }
                        });
                    }

                    $checkbox.on('change', function() {
                        toggleFields(this.checked);
                    });
                });


                $('.sidepanel form', that).on('submit', function(){
                    let formData = new FormData(this);
                    formData.delete('form_id');
                    formData.delete('form_token');

                    let finalData = new FormData();

                    for (let [key, value] of formData.entries()) {
                        finalData.append(`data[${key}]`, value);
                    }

                    identifiers.forEach((identifier, index) => {
                        finalData.append(`items[${index}]`, encodeURIComponent(JSON.stringify(identifier)));
                    });

                    $.ajax({
                        type: "POST",
                        url: massEditUrl,
                        data: finalData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            $(that).appAdmin('closeSidePanel');

                            // reload page in order to update table content if needed
                            document.location.reload();
                        },
                        error: function() {
                            $(that).appAdmin('showAlertDialog', {
                                title: 'Error',
                                message: 'Generic Error',
                                type: 'error',
                            });
                        }
                    });

                });
            });

        },
        askAI: function(type, params, targetOrCallback) {
            let that = this;
            let AIUrl = null;

            const aiModel = $(that).appAdmin('getSettings').availableAImodels.find((model) => model.code == type);
            if (aiModel) {
                AIUrl = aiModel.aiURL;
            } else {
                $(that).appAdmin('showAlertDialog', {
                    title: 'Not available',
                    message: 'AI model ' + type + ' is not available',
                    type: 'warning',
                });
                return;
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
        updateUserUiSettings: function(settings, succesCallback) {
            let that = this;
            let uIsettingsUrl = $(that).appAdmin('getSettings').uIsettingsUrl;
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

            let uIsettingsUrl = $(that).appAdmin('getSettings').uIsettingsUrl;
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
        
            let notificationsUrl = $(that).appAdmin('getSettings').notificationsUrl;
            let notificationDismissUrl = $(that).appAdmin('getSettings').notificationCrudUrl;

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
        showAlertDialog: function(options) {
            const defaults = {
                title: 'Alert',
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

            // Eventi pulsanti
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
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.appAdmin.defaults = {
        'rootUrl': null,
        'checkLoggedUrl': null,
        'logoutUrl': null,
        'uIsettingsUrl': null,
        'currentRoute': null,
        'notificationsUrl': null,
        'notificationCrudUrl': null,
        'aiAvailable': null,
        'availableAImodels': [],
        'massDeleteUrl': null,
        'massEditUrl': null,
    }
})(jQuery);
