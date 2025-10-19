(function($){
    window.appAdminMethodsListing = {
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
                $(that).appAdmin('listingToggleAll', '#'+$table.attr('id'), this);
            });

            $('.table-row-selector', table).on('change', function(evt) {
                let $tr = $(evt.target).closest('tr');
                if ($(this).is(':checked')) {
                    $tr.addClass('selected');
                } else {
                    $tr.removeClass('selected');
                }
            });

            $table.find('tbody tr.selectable').on('click', function(evt){
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
            });
            $table.find('tr[data-dblclick]').on('dblclick', function(evt){
                if ($(evt.target).is('td, tr')) {
                    clearTimeout(clickTimer);

                    let $tr = $(evt.target).closest('tr');

                    if ($tr.data('dblclick')) {
                        document.location = $tr.data('dblclick').replace("&amp;","&");
                    }
                }
            });
        },
        listingGrid: function(grid) {
            let that = this;

            let $grid = $(grid);

            let clickTimer = null;
            const delay = 250;

            $('[data-bs-toggle="collapse"]', $grid).on('click', (e) => {
                e.preventDefault();
                const target = document.querySelector($(e.target).data('bsTarget'));
                if (target) new bootstrap.Collapse(target);
           });

            // initially uncheck every row selector
            $('.table-row-selector, #listing-table-toggle-all', grid).prop('checked', false);

            $('#listing-table-toggle-all', grid).on('change', function() {
                $(that).appAdmin('listingToggleAll', '#'+$grid.attr('id'), this);
            });

            $('.table-row-selector', grid).on('change', function(evt) {
                let $selectable = $(evt.target).closest('.selectable');
                if ($(this).is(':checked')) {
                    $selectable.addClass('selected');
                } else {
                    $selectable.removeClass('selected');
                }
            });

            $grid.find('.selectable').on('click', function(evt){
                if ($(evt.target).is('div')) {
                    clearTimeout(clickTimer);

                    let $selectable = $(evt.target).closest('.selectable');

                    clickTimer = setTimeout(function() {                        
                        let identifier = $selectable.data('_item_pk');
                        if (undefined !== identifier) {
                            let $checkbox = $('input[type="checkbox"].table-row-selector:eq(0)', $selectable);
                            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                        }
                    }, delay);
                }
            });
            $grid.find('[data-dblclick]').on('dblclick', function(evt){
                if ($(evt.target).is('div')) {
                    clearTimeout(clickTimer);

                    let $containerTarget = $(evt.target).closest('.dblclick, .selectable');

                    if ($containerTarget.data('dblclick')) {
                        document.location = $containerTarget.data('dblclick').replace("&amp;","&");
                    }
                }
            });
        },
        listingToggleAll: function(tableSelector, element) {
            let $element = $(element);
            let $table = $(tableSelector);
            let $checkboxes = $('input[type="checkbox"].table-row-selector', $table);
            $checkboxes.prop('checked', $element.prop('checked')).trigger('change');
        },
        listingGetSelected: function(tableSelector) {
            let $table = $(tableSelector);
            let $checkboxes = $('input[type="checkbox"].table-row-selector:checked', $table);
            return $checkboxes.map(function(index, checkbox) {
                let $checkbox = $(checkbox);
                let identifier = $($checkbox.closest('.selectable')).data('_item_pk');
                if (undefined !== identifier) {
                    return identifier;
                }
                return null;
            }).get().filter((el) => el);
        },
        listingDeleteSelected: function(tableSelector, className) {
            let that = this;
            let identifiers = $(that).appAdmin('listingGetSelected', tableSelector);
            if (identifiers.length == 0) {
                $(that).appAdmin('showAlertDialog', {
                    title: __('No elements selected'),
                    message: __('Please choose at least one element to delete'),
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

            form.submit();
        },
        listingEditSelected: function(tableSelector, controllerClassName, modelClassName, btnElement = null) {
            let that = this;
            let identifiers = $(that).appAdmin('listingGetSelected', tableSelector);
            if (identifiers.length == 0) {
                $(that).appAdmin('showAlertDialog', {
                    title: __('No elements selected'),
                    message: __('Please choose at least one element to edit'),
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
        initMediaContextMenu: function(element) {
            let that = this;

            let $element = $(element);
            let tableSelector = '#'+$(element).attr('id');

            const STORAGE_KEY = 'appAdminMediaClipboard';

            function getPersistentClipboard() {
                const stored = sessionStorage.getItem(STORAGE_KEY);
                try {
                    return stored ? JSON.parse(stored) : { action: null, items: [] };
                } catch (e) {
                    return { action: null, items: [] };
                }
            }

            function setPersistentClipboard(action, items) {
                const data = { action: action, items: items };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                return data;
            }

            function resetPersistentClipboard() {
                sessionStorage.removeItem(STORAGE_KEY);
                return { action: null, items: [] };
            }
            // ----------------------------------------------------

            let clipboard = getPersistentClipboard();

            const $menu = $(
                '<ul id="mediaContextMenu" class="dropdown-menu" style="display:none; position:absolute; z-index:9999;">' +
                '</ul>'
            ).appendTo("body");

            $(document).on("click", () => $menu.hide());

            $element.on("contextmenu", function (e) {
                const invalidTargets = ["a", "button", "input", "label", "select", "textarea", "i"];
                if ($(e.target).closest(invalidTargets.join(",")).length) return;

                e.preventDefault();

                $menu.html(
                    '<li><a class="dropdown-item action-copy" href="#">📄 '+__('Copy')+'</a></li>' +
                    '<li><a class="dropdown-item action-cut" href="#">✂️ '+__('Cut')+'</a></li>' +
                    '<li><a class="dropdown-item action-paste" href="#">📋 '+__('Paste')+'</a></li>'
                );

                clipboard = getPersistentClipboard(); 

                let identifiers = $(that).appAdmin('listingGetSelected', tableSelector);
                const hasSelectionOnPage = identifiers.length > 0;

                if (!hasSelectionOnPage && (!clipboard.items.length || !clipboard.action)) return;

                $menu.find(".action-copy, .action-cut").toggle(hasSelectionOnPage);
                $menu.find(".action-paste").toggle(!!clipboard.items.length);

                $menu.css({ top: e.pageY, left: e.pageX }).show();
            });

            // Copy
            $(document).on("click", "#mediaContextMenu .action-copy", function (e) {
                e.preventDefault();

                let identifiers = $(that).appAdmin('listingGetSelected', tableSelector);

                if (!identifiers.length) {
                    $(that).appAdmin('warning', __('No element selected'));
                    return;
                }

                clipboard = setPersistentClipboard("copy", identifiers);
                $(that).appAdmin('notify', __('%d elements copied', clipboard.items.length));
                $menu.hide();
            });

            // Cut
            $(document).on("click", "#mediaContextMenu .action-cut", function (e) {
                e.preventDefault();

                let identifiers = $(that).appAdmin('listingGetSelected', tableSelector);
                
                if (!identifiers.length) {
                    $(that).appAdmin('warning', __('No element selected'));
                    return;
                }

                clipboard = setPersistentClipboard("move", identifiers);
                $(that).appAdmin('notify', __('%d elements cut', clipboard.items.length));
                $menu.hide();
            });

            // Paste
            $(document).on("click", "#mediaContextMenu .action-paste", function (e) {
                e.preventDefault();
                
                clipboard = getPersistentClipboard(); 
                if (!clipboard.items.length || !clipboard.action) return;

                const urlParams = new URLSearchParams(window.location.search); 
                const parentId = urlParams.get('parent_id');
                const pasteAction = clipboard.action;
                const itemsToPaste = clipboard.items;
                
                clipboard = resetPersistentClipboard();

                $.ajax({
                    url: $(that).appAdmin('getSettings').mediaPasteUrl,
                    type: "POST",
                    data: {
                        action: pasteAction,
                        ids: itemsToPaste,
                        parent_id: parentId
                    },
                    success: function () {
                        $(that).appAdmin('success', __('Operation complete'), () => document.location.reload());
                    },
                    error: function (xhr) {
                        try {
                            let json = JSON.parse(xhr.responseText);
                            $(that).appAdmin('error', __('Error during operation: %s', json.message));
                        } catch (e) {
                            $(that).appAdmin('error', __('Error during operation: %s', xhr.responseText));
                        }
                    }
                });

                $menu.hide();
            });
        },
    };
})(jQuery);