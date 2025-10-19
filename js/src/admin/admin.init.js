(function($){

    // 1. Unione di tutti i metodi
    const allMethods = {};
    $.extend(allMethods, window.appAdminMethodsBase);
    $.extend(allMethods, window.appAdminMethodsPanel);
    $.extend(allMethods, window.appAdminMethodsListing);
    $.extend(allMethods, window.appAdminMethodsAI);
    $.extend(allMethods, window.appAdminMethodsUI);
    $.extend(allMethods, window.appAdminMethodsAlert);

    // Rimuovi gli oggetti temporanei dall'ambito globale per pulizia
    delete window.appAdminMethodsBase;
    delete window.appAdminMethodsPanel;
    delete window.appAdminMethodsListing;
    delete window.appAdminMethodsAI;
    delete window.appAdminMethodsUI;
    delete window.appAdminMethodsAlert;

    // 2. Definizione del plugin (usa allMethods per la chiamata)
    $.fn.appAdmin = function (methodOrOptions) {
        // Cerca in allMethods
        if ( allMethods[methodOrOptions] ) {
            return allMethods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return $.fn.appAdmin.methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.appAdmin' );
        }
    };

    // 3. Definizione del metodo init e assegnazione dei metodi uniti
    $.fn.appAdmin.methods = $.extend({
        init : function(options) {
            let instance = this;
            // Iterate and reformat each matched element.
            return this.each(function() {
                let settings = $.extend({}, $.fn.appAdmin.defaults, options);
                let $elem = $( this );

                instance.settings = settings;
                instance.$elem = $elem;

                $elem.data('appAdmin', instance);

                loadTranslations($elem.appAdmin('getSettings').currentLocale);

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

                $('#dashboard-layout-selector').on('change', function () {
                    $elem.appAdmin('updateUserUiSettings', 
                        {'dashboardLayout': $(this).is(':checked') ? 'by_section' : 'list'},
                        function (data) {
                            document.location.reload();
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

                        let openPanelWidth = $btnElement.data('panelwidth') || $btnElement.data('panelWidth') || '60%';
                        $elem.appAdmin('loadPanelContent', $(this).attr('title') || '', $(this).attr('href'), true, true, function() {
                            $('.spinner-border', $btnElement).remove();
                            $btnElement.removeClass('loading disabled').css('pointer-events', '');
                        }, openPanelWidth);
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

                $('#actionButtonsCollapse').on('click', function () {
                    $('#nav-action-buttons ul.navbar-nav').toggleClass('d-none');
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

                $('#pagination-layout-selector a').on('click', function(evt){
                    evt.preventDefault();
                    $elem.appAdmin('updateUserUiSettings', 
                        {'currentRoute': $elem.appAdmin('getSettings').currentRoute, 'layout': $(this).data('layout')},
                        function (data) {
                            document.location.reload();
                        }
                    );
                });

                $('table[id^="listing-table-"]').each(function(index, table) {
                    $elem.appAdmin('listingTable', table);
                });

                $('div[id^="listing-grid-"], div[id^="listing-compact_grid-"]').each(function(index, grid) {
                    $elem.appAdmin('listingGrid', grid);
                });

                $('#listing-grid-mediaelement, #listing-table-mediaelement').each(function(index, element) {
                    $elem.appAdmin('initMediaContextMenu', element);
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

                $('#duplicate-btn', $elem).on('click', function(evt) {
                    evt.preventDefault();

                    $elem.appAdmin('showAlertDialog', {
                        title: __('Confirm duplicate?'),
                        message: __('Do you confirm element duplication?'),
                        okText: __('Yes, Continue'),
                        cancelText: __('No, Cancel'),
                        onConfirm: function() {
                            document.location.href = $(evt.target).attr('href');
                        }
                    });
                });

                $('#login-as-btn').on('click', function(evt) {
                    evt.preventDefault();
                    $elem.appAdmin('showAlertDialog', {
                        title: __('Login as user'),
                        message: __('In order to login as this user, copy the following link and open it in a new browser window or incognito mode:') + '\n\n<textarea id="login-as-textarea" style="width: 100%; height: 200px;">' + $(evt.target).attr('href') + '</textarea><div class="text-right mt-2"><button data-target="login-as-textarea" class="btn btn-sm btn-secondary copy-to-clipboard">' + __('Copy to clipboard') + '</button></div>',
                        type: 'info',
                    });
                });

                $(document).on('click', '.copy-to-clipboard', function(evt) {
                    const $target = $('#'+$(evt.target).data('target'));
                    if ($target) {
                        $elem.appAdmin('copyToClipboard', $target.text());
                        $elem.appAdmin('showTooltip', evt.target, __('Copied!'));
                    }
                });
            });
        },
    }, allMethods); // Estende con tutti i metodi

    $.fn.appAdmin.defaults = window.appAdminDefaults;
})(jQuery);