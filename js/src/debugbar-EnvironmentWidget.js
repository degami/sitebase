(function () {
  if (typeof window.PhpDebugBar === 'undefined') {
    // Se la DebugBar non è ancora caricata, aspetta l'evento globale
    document.addEventListener('debugbar:loaded', registerWidget);
  } else {
    registerWidget();
  }

  function registerWidget() {
    if (
      typeof window.PhpDebugBar === 'undefined' ||
      typeof window.PhpDebugBar.Widget === 'undefined'
    ) {
      return;
    }

    if (window.PhpDebugBar.Widgets.EnvironmentWidget) {
      return; // evita doppie registrazioni
    }

    var EnvironmentWidget = PhpDebugBar.Widgets.EnvironmentWidget = PhpDebugBar.Widget.extend({
        initialize: function(options) {
            if (!options['itemRenderer']) {
                options['itemRenderer'] = this.itemRenderer;
            }
            this.set(options);
        },

        render: function() {
            this.bindAttr(['data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var data = this.get('data');

                if (data.flags) {
                    this.$el.append('<h6 style="margin-top: 15px; margin-bottom: 5px;">Environment</h6>');
                    const dl = $('<dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-htmlvarlist"></dl>');

                    const check_mark = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="green"><path d="M6.173 12.414l-4.243-4.243 1.414-1.414 2.829 2.828 5.657-5.657 1.414 1.414z"/></svg>';
                    const cross = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="red"><path d="M9.414 8l4.95-4.95-1.414-1.414L8 6.586 3.05 1.636 1.636 3.05 6.586 8l-4.95 4.95 1.414 1.414L8 9.414l4.95 4.95 1.414-1.414z"/></svg>'

                    for (const k in data.flags) {
                        const dt = $('<dt class="phpdebugbar-widgets-key"></dt>');
                        dt.append('<span title="' + k + '">' + k + '</span>');
                        const dd = $('<dd class="phpdebugbar-widgets-value">'+ (String(data.flags[k]) == 'true' ? check_mark : cross) +'</dd>');
                        dl.append(dt);
                        dl.append(dd);
                    }

                    this.$el.append(dl);
                }

                if (data.variables) {
                    this.$el.append('<h6 style="margin-top: 15px; margin-bottom: 5px;">ENV variables</h6>');

                    const dl = $('<dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-htmlvarlist"></dl>');
                    for (const k in data.variables) {
                        const dt = $('<dt class="phpdebugbar-widgets-key"></dt>');
                        dt.append('<span title="' + k + '">' + k + '</span>');
                        const dd = $('<dd class="phpdebugbar-widgets-value">'+String(data.variables[k])+'</dd>');
                        dl.append(dt);
                        dl.append(dd);
                    }

                    this.$el.append(dl);
                }

            });
        }

    });

  }
})();
