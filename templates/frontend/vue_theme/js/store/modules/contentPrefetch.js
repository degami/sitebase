const state = () => ({
  initialized: false,
  loading: false
});

const mutations = {
  setInitialized(state, val) {
    state.initialized = val;
  },
  setLoading(state, val) {
    state.loading = val;
  }
};

const actions = {
  async prefetchAll({ commit, dispatch, state, rootGetters }) {
    if (state.initialized) {
      return;
    }

    commit("setLoading", true);
    try {
        const defaultWebsiteId = await dispatch('configuration/getWebsiteId', { 
            siteDomain: window.location.hostname 
        }, { root: true });
        const website_id = rootGetters["appState/website_id"] || defaultWebsiteId;

        // richiami entrambi i moduli in parallelo
        await Promise.all([
            dispatch('appState/fetchTranslations', { root: true }),
            dispatch("pages/fetchAllPages", { filters: null }, { root: true }),
            dispatch("terms/fetchAllTerms", { filters: null, maxLevels: 2 }, { root: true }),
        ]);

        let rewrites = await dispatch('rewrites/fetchRewrites', { websiteId : website_id }, { root: true });
        if (rewrites) {
          rewrites = [...rewrites];

          // sort by current rewrite first, then by current locale, then others
          const currentPath = '/' + window.location.pathname.replace(/^\/+|\/+$/g, ''); // rimuovi gli slash iniziali e finali
          const locale = rootGetters['appState/locale'] || 'en';
          rewrites.sort((a, b) => {
              if (a.url === currentPath) return -1;
              if (b.url === currentPath) return 1;
              if (a.locale === locale && b.locale !== locale) return -1;
              if (b.locale === locale && a.locale !== locale) return 1;
              return 0;
          });
          if (rewrites.length > 0) {
              await dispatch("pageregions/fetchPageregions", { param: rewrites[0].id }, { root: true });

              window.setTimeout(() => {
                rewrites.slice(1).forEach(rewrite => {
                    dispatch("pageregions/fetchPageregions", { param: rewrite.id }, { root: true });
                });
              }, 1000);
          }
        }

        commit("setInitialized", true);
        console.log("Prefetch completato!");
    } catch (err) {
      console.error("Errore nel prefetch:", err);
    } finally {
      commit("setLoading", false);
    }
  }
};

export default {
  namespaced: true,
  state,
  mutations,
  actions
};
