import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Page from '../components/Page.vue';
import News from '../components/News.vue';
import ListNews from '../components/ListNews.vue';
import Event from '../components/Event.vue';
import ListEvents from '../components/ListEvents.vue';
import Taxonomy from '../components/Taxonomy.vue';

import store from '../store';

const routes = [
  { path: '/', component: Home, props: true },

  { path: '/page/:id', component: Page, props: true },
  { path: '/taxonomy/:id', component: Taxonomy, props: true },
  { path: '/news/:id', component: News, props: true },
  { path: '/news', component: ListNews, props: true },
  { path: '/event/:id', component: Event, props: true },
  { path: '/events', component: ListEvents, props: true },

  // localized versions

  { path: '/:locale/page/:id', component: Page, props: true },
  { path: '/:locale/taxonomy/:id', component: Taxonomy, props: true },
  { path: '/:locale/news/:id', component: News, props: true },
  { path: '/:locale/news', component: ListNews, props: true },
  { path: '/:locale/event/:id', component: Event, props: true },
  { path: '/:locale/events', component: ListEvents, props: true },
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
    const locale = store.getters['appState/locale'] || 'en';
    const website_id = store.getters['appState/website_id']  || websiteId;

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
//    next(rewrite.route); // Se esiste una riscrittura, fai il redirect

    // Crea un nuovo path che includa il prefisso della lingua (es: /it/news)
    const newPath = `/${rewrite.locale}${rewrite.route}`;

    // Se l'URL attuale non corrisponde al path con il locale, fai il redirect
    if (to.path !== newPath) {
      next({ path: newPath, replace: true });  // Redirigi con il nuovo path (preservando il locale)
    } else {
      next();  // Se già siamo sul path giusto, continua normalmente
    }

  } else {
    next(); // Altrimenti continua normalmente
  }
});

export default router;