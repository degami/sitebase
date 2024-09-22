import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Page from '../components/Page.vue';
import News from '../components/News.vue';
import Event from '../components/Event.vue';
import Taxonomy from '../components/Taxonomy.vue';

import store from '../store';

const routes = [
  { path: '/', component: Home },
  { path: '/page/:id', component: Page },
  { path: '/taxonomy/:id', component: Taxonomy },
  { path: '/news/:id', component: News },
  { path: '/event/:id', component: Event },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

const websiteId = await store.dispatch('configuration/getWebsiteId', { 
  siteDomain: window.location.hostname 
})

router.beforeEach(async (to, from, next) => {
  // Controlla se le rewrites sono già caricate
  if (!store.state.rewrites) {
    // Se non sono caricate, effettua la chiamata per ottenerle
    await store.dispatch('rewrites/fetchRewrites', websiteId);
  }

  const rewrite = await store.dispatch('rewrites/findRewriteByUrl', { url: to.path, websiteId });

  if (rewrite) {
    console.log(rewrite);
    await store.dispatch('apolloClient/updateLocale', rewrite.locale);
    next(rewrite.route); // Se esiste una riscrittura, fai il redirect
  } else {
    next(); // Altrimenti continua normalmente
  }
});

export default router;