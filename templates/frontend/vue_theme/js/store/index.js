// js/store/index.js
import { createStore } from 'vuex';
import appState from './modules/appState';
import apolloClient  from './modules/apolloClient';
import configuration from './modules/configuration';
import menuTree from './modules/menuTree';
import pages from './modules/pages';
import pageregions from './modules/pageregions';
import rewrites from './modules/rewrites';
import news from './modules/news';
import events from './modules/events';
import terms from './modules/terms';

const store = createStore({
  modules: {
    appState,
    apolloClient,
    configuration,
    menuTree,
    pages,
    pageregions,
    rewrites,
    news,
    events,
    terms,
  },
});

export default store;
