import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import ResolveComponent from '../components/ResolveComponent.vue'; // Nuovo componente per risolvere le rotte dinamiche
import store from '../store';

const routes = [
  // Route Home
  { path: '/', component: Home, props: true },
  // Rotte dinamiche per le riscritture, indipendentemente dal tipo di contenuto
  { path: '/:locale/:customUrl', component: ResolveComponent, props: true },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Ottenere l'ID del sito web al caricamento del router
const websiteId = await store.dispatch('configuration/getWebsiteId', { 
  siteDomain: window.location.hostname 
});

router.beforeEach(async (to, from, next) => {
  const appInstance = router.app;
  if (appInstance) {
    const locale = store.getters['appState/locale'] || 'en';
    const website_id = store.getters['appState/website_id'] || websiteId;

    // Imposta i parametri locali e website_id sulla rotta
    to.params.locale = locale;
    to.params.website_id = website_id;
  }

  // Controlla se le riscritture sono già caricate
  if (!store.state.rewrites) {
    await store.dispatch('rewrites/fetchRewrites', websiteId);
  }

  next(); // Procedi senza redirect
});

export function getComponentMap() {
  return {
    'event': () => import('@/components/Event.vue'),
    'news': () => import('@/components/News.vue'),
    'page': () => import('@/components/Page.vue'),
    'taxonomy': () => import('@/components/Taxonomy.vue'),
    'newslist': () => import('@/components/ListNews.vue'),
    'eventslist': () => import('@/components/ListEvents.vue'),
  };
}

export default router;
