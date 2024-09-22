import { ApolloClient, InMemoryCache } from '@apollo/client';
import { createHttpLink } from '@apollo/client/link/http';

const state = {
  apolloClient: null,
  locale: null, // Locale iniziale
};

const mutations = {
  SET_APOLLO_CLIENT(state, client) {
    state.apolloClient = client;
  },
  SET_LOCALE(state, locale) {
    state.locale = locale;
  },
};

const actions = {
  initializeApolloClient({ state, commit }) {
    const baseUri = window.location.origin;
    const locale = state.locale;
    const uri = locale ? `${baseUri}/graphql/${locale}` : `${baseUri}/graphql`;

    const httpLink = createHttpLink({
      uri: uri,
    });

    const apolloClient = new ApolloClient({
      link: httpLink,
      cache: new InMemoryCache(),
    });
    console.log('ApolloClient Initialized');
    commit('SET_APOLLO_CLIENT', apolloClient);
  },
  updateLocale({ commit, dispatch }, locale) {
    commit('SET_LOCALE', locale);
    console.log('ApolloClient locale set to '+locale);
    // Rinnova l'istanza dell'Apollo Client ogni volta che cambia il locale
    dispatch('initializeApolloClient');
  },
  async getApolloClient({ state, dispatch }) {
    if (state.apolloClient == null) {
      await dispatch('initializeApolloClient');
    }
    return state.apolloClient;
  },
};

const getters = {
  apolloClient: (state) => state.apolloClient,
  locale: (state) => state.locale,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};
