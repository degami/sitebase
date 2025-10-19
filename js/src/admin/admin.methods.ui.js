(function($){
    window.appAdminMethodsUI = {
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
                                    $('#' + dialogId).fadeOut(function(){
                                        $(this).remove();
                                    });
                                },
                                error: function(xhr) {
                                    console.error('Errors on notification close:', dialogId);
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
    };
})(jQuery);