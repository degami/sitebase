tinymce.PluginManager.add('block_plugin', function(editor, url) {
    // Bottone per inserire un blocco
    editor.ui.registry.addButton('insert_block', {
        tooltip: 'Insert a Block',
        icon: 'visualblocks',
        onAction: function() {
            const adminSettings = jQuery('#admin').appAdmin('getSettings');
            const blocksCrudUrl = adminSettings.rootUrl.replace(/\/index$/, '') + '/json/blockslist';

            fetch(blocksCrudUrl)
                .then(response => response.json())
                .then(response => {
                    const options = response.data.map(block => ({
                        type: 'option',
                        text: block.name + (block.locale ? ` (${block.locale})` : ''),
                        value: String(block.id)
                    }));

                    editor.windowManager.open({
                        title: 'Select a Block',
                        body: {
                            type: 'panel',
                            items: [
                                {
                                    type: 'selectbox',
                                    name: 'block_id',
                                    label: 'Choose Block',
                                    items: options
                                }
                            ]
                        },
                        buttons: [
                            {
                                type: 'submit',
                                text: 'Insert'
                            },
                            {
                                type: 'cancel',
                                text: 'Cancel'
                            }
                        ],
                        onSubmit: function(api) {
                            const data = api.getData();
                            const blockId = data.block_id;

                            if (blockId) {
                                const placeholder = `<div style="display: inline-block" class="block-placeholder" contenteditable="false" data-block-id="${blockId}">[Block: ${blockId}]</div>`;
                                editor.insertContent(placeholder);
                                api.close();

                                window.setTimeout(function(){
                                    editor.fire('updateBlockContents');
                                }, 250);
                            }
                        }
                    });
                });
        }
    });

    editor.on('init', function(event) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = editor.getBody().innerHTML;

        tempDiv.innerHTML = tempDiv.innerHTML.replace(/\[Block:\s*(\d+)\]/g, function(_, blockId) {
            return `<div style="display: inline-block" class="block-placeholder" contenteditable="false" data-block-id="${blockId}">[Block: ${blockId}]</div>`;
        });

        editor.getBody().innerHTML = tempDiv.innerHTML;

        window.setTimeout(function(){
            editor.fire('updateBlockContents');
        }, 250);
    })

    editor.on('GetContent', function(event) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = event.content;

        tempDiv.querySelectorAll('.block-placeholder').forEach(el => {
            const blockId = el.getAttribute('data-block-id');
            el.replaceWith(`[Block: ${blockId}]`);
        });

        editor.getBody().innerHTML = tempDiv.innerHTML;
    });

    editor.on('updateBlockContents', function() {
        let content = editor.getBody().innerHTML;
        editor.getBody().querySelectorAll('.block-placeholder:not(.replaced)').forEach(el => {
            const blockId = el.getAttribute('data-block-id');
            const urlParams = new URLSearchParams(window.location.search);
            const pageId = urlParams.get('page_id');
    
            const adminSettings = jQuery('#admin').appAdmin('getSettings');
            const previewUrl = adminSettings.rootUrl.replace(/\/index$/, '') + `/json/getblockpreview?block_id=${blockId}&page_id=${pageId}`;
        
            fetch(previewUrl)
                .then(response => response.json())
                .then(data => {
                    let oldContent = el.outerHTML;
                    
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.data;

                    el.classList.add('replaced');
                    el.innerHTML = tempDiv.innerHTML; 
    
                    content = content.replace(oldContent, el.outerHTML);
                    editor.setContent(content);
                })
                .catch(err => console.error('Errore nel caricamento anteprima blocco:', err));
        });
    });
    
});
