import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Page from '../components/Page.vue';
import News from '../components/News.vue';
import ListNews from '../components/ListNews.vue';
import Event from '../components/Event.vue';
import Taxonomy from '../components/Taxonomy.vue';

import store from '../store';

const routes = [
  { path: '/', component: Home, props: true },
  { path: '/page/:id', component: Page, props: true },
  { path: '/taxonomy/:id', component: Taxonomy, props: true },
  { path: '/news/:id', component: News, props: true },
  { path: '/news', component: ListNews, props: true },
  { path: '/event/:id', component: Event, props: true },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

const websiteId = await store.dispatch('configuration/getWebsiteId', { 
  siteDomain: window.location.hostname 
})

router.beforeEach(async (to, from, next) => {
  const appInstance = router.app;
  if (appInstance) {
    const locale = appInstance.locale || 'en';
    const website_id = appInstance.website_id || 1;

    // Passa i dati come props alla rotta
    to.params.locale = locale;
    to.params.website_id = website_id;
  }

  // Controlla se le rewrites sono già caricate
  if (!store.state.rewrites) {
    // Se non sono caricate, effettua la chiamata per ottenerle
    await store.dispatch('rewrites/fetchRewrites', websiteId);
  }

  const rewrite = await store.dispatch('rewrites/findRewriteByUrl', { url: to.path, websiteId });

  if (rewrite) {
    await store.dispatch('apolloClient/updateLocale', rewrite.locale);
    next(rewrite.route); // Se esiste una riscrittura, fai il redirect
  } else {
    next(); // Altrimenti continua normalmente
  }
});

export default router;