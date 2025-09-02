tinymce.PluginManager.add('ai_translate_plugin', function(editor, url) {

    function translateWithAI(preferredAI) {
        const elementContent = editor.getContent();
        const locale = jQuery('#locale').val() || 'en';
        const promptText = `Translate the following text into language with iso2 code "${locale}":\n\n${elementContent}`;

        editor.setProgressState(true);

        jQuery('#admin').appAdmin('askAI', preferredAI, { prompt: promptText }, function(data) {
            editor.setProgressState(false);

            if (data.success) {
                const match = data.text.match(/```json([\s\S]*?)```/);
                let translated = data.text;

                if (match && match[1]) {
                    try {
                        const json = JSON.parse(match[1].trim());
                        translated = json.translation || translated;
                    } catch (e) {
                        console.warn("JSON parse error:", e);
                    }
                }

                editor.setContent(translated);
            } else {
                alert("AI Error: " + data.message);
            }
        });
    }

    editor.ui.registry.addButton('ai_translate', {
        tooltip: 'Translate with AI',
        icon: 'translate',
        onAction: function() {
            const adminSettings = jQuery('#admin').appAdmin('getSettings');

            const models = adminSettings.availableAImodels;

            if (models.length === 1) {
                translateWithAI(models[0].code);
                return;
            }


            jQuery('#admin').appAdmin('getUserUiSettings', function(userSettings) {
                const userPreferredAI = userSettings.preferredAI || null;

                const options = models.map(model => ({
                    type: 'option',
                    text: model.name,
                    value: String(model.code)
                }));

                editor.windowManager.open({
                    title: 'Translate with AI',
                    body: {
                        type: 'panel',
                        items: [
                            {
                                type: 'selectbox',
                                name: 'preferred_aI',
                                label: 'Choose AI Model',
                                items: options
                            }
                        ]
                    },
                    buttons: [
                        { type: 'submit', text: 'Translate' },
                        { type: 'cancel', text: 'Cancel' }
                    ],
                    initialData: {
                        preferred_aI: userPreferredAI
                    },
                    onSubmit: function(api) {
                        const data = api.getData();
                        const preferredAI = data.preferred_aI;
                        api.close();
                        translateWithAI(preferredAI);
                    }
                });
            });
        },
        onSetup: function(api) {
            const adminSettings = jQuery('#admin').appAdmin('getSettings');
            if (!adminSettings.aiAvailable) {
                api.setEnabled(false);
                setTimeout(() => {
                    const btn = editor.editorContainer.querySelector('[data-mce-name="ai_translate"]');
                    if (btn) { btn.style.display = 'none'; }
                }, 0);
            }
        },
    });    
});
