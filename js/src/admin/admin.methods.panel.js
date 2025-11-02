(function($){
    window.appAdminMethodsPanel = {
        openSidePanel: function(openPanelWidth = '60%') {
            let that = this;

            $(that).appAdmin('showOverlay');
            $('.sidepanel', this).css({'width': openPanelWidth});

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

            const unbinders = $(this).data('sidePanelUnbinders') || [];
            unbinders.forEach(unbind => {
                if (typeof unbind === 'function') {
                    unbind();
                }
            });
            $(this).removeData('sidePanelUnbinders');
        },
        loadPanelContent: function(title, url, open_panel, store_last_url, afterloadCallback = null, openPanelWidth = '60%') {
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
                            if( $.trim(data.js) != '' ){
                                eval(data.js);
                                return;
                            };

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
                    $(that).appAdmin('openSidePanel', openPanelWidth);
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
    };
})(jQuery);