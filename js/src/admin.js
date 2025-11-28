const $ = require('jquery'); // Usa require() anche se è esterno. Rollup lo mappa.

const select2Module = require('select2');

// Se il modulo Select2 è una funzione factory, la chiamiamo con $ (jQuery)
if (typeof select2Module === 'function') {
    select2Module($);
} else {
    // Altrimenti, assumiamo che sia un modulo side-effecting che si è auto-eseguito
}

require('jquery.cookie');
require('highlightjs');
require('moment');
require('driver.js');
require('shepherd.js'); 

require('./i18n.js');

require('./admin/admin.dependencies.js');
require('./admin/admin.tinymce.js');
require('./admin/admin.defaults.js');

require('./admin/admin.methods.base.js');
require('./admin/admin.methods.panel.js');
require('./admin/admin.methods.listing.js');
require('./admin/admin.methods.ai.js');
require('./admin/admin.methods.ui.js');
require('./admin/admin.methods.alert.js');

require('./admin/admin.init.js');
