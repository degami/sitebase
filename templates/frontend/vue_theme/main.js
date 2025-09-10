import $ from 'jquery';
window.$ = window.jQuery = $;

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

import { createApp, provide, h } from 'vue';
import App from './js/components/App.vue';
import { DefaultApolloClient } from '@vue/apollo-composable'
import store from './js/store';
import router from './js/router';


const apolloClient = store.getters['apolloClient/apolloClient'];

const app = createApp({
  setup () {
    provide(DefaultApolloClient, apolloClient)
  },

  render: () => h(App),
})

// Crea l'app Vue
//const app = createApp(App);

app.use(store);
app.use(router); 

store.dispatch("contentPrefetch/prefetchAll");

try {
  app.mount('#app');
} catch (e) {
  console.error("Error mounting Vue:", e);
}
