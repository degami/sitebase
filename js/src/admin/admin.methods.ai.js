const $ = require('jquery');
const moment = require('moment');

window.appAdminMethodsAI = {
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
    askAI: function(type, params, targetOrCallback) {
        let that = this;
        let AIUrl = null;

        const aiModel = $(that).appAdmin('getSettings').availableAImodels.find((model) => model.code == type);
        if (aiModel) {
            AIUrl = aiModel.aiURL;
        } else {
            $(that).appAdmin('showAlertDialog', {
                title: __('Not available'),
                message: __('AI model %s is not available', type),
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
};
