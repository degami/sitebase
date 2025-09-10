// js/store/index.js
import { createStore } from 'vuex';
import apolloClient  from './modules/apolloClient';
import configuration from './modules/configuration';
import appState from './modules/appState';
import menuTree from './modules/menuTree';
import pages from './modules/pages';
import pageregions from './modules/pageregions';
import rewrites from './modules/rewrites';
import news from './modules/news';
import events from './modules/events';
import terms from './modules/terms';
import medias from './modules/medias';
import links from './modules/links'
import search from './modules/search';
import website from './modules/website';
import contacts from './modules/contacts';
import contentPrefetch from './modules/contentPrefetch';

const store = createStore({
  modules: {
    apolloClient,
    website,
    configuration,
    appState,
    menuTree,
    pages,
    pageregions,
    rewrites,
    news,
    events,
    terms,
    links,
    medias,
    search,
    contacts,
    contentPrefetch,
  },
});

export default store;
