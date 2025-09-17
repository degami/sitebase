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
        const website = await dispatch('appState/getWebsite', null, { root: true });
        const website_id = website?.id;

        await Promise.all([
            dispatch('appState/fetchTranslations', { root: true }),
            dispatch("pages/fetchAllPages", { filters: null }, { root: true }),
            dispatch("terms/fetchAllTerms", { filters: null, maxLevels: 2 }, { root: true }),
        ]);

        let rewrites = await dispatch('rewrites/fetchRewrites', { websiteId : website_id }, { root: true });

        // we can only prefetch current route pageregions, as setting appState locale will trigger a full prefetch
        const currentRewrite = rewrites.find(rw => rw.url === '/' + window.location.pathname.replace(/^\/+|\/+$/g, ''));
        if (currentRewrite) {
          await dispatch("pageregions/fetchPageregions", { param: currentRewrite.id }, { root: true });
        }

        /*
        if (rewrites) {
          rewrites = [...rewrites];

          // sort by current rewrite first, then by current locale, then others
          const currentPath = '/' + window.location.pathname.replace(/^\/+|\/+$/g, ''); // rimuovi gli slash iniziali e finali
          const locale = rootGetters['appState/locale'] || 'en';

          //filter out rewrites that do not match the current locale
          rewrites = rewrites.filter(rw => (!rw.locale || rw.locale === locale) && rw.route.match(/^\/(page|taxonomy)(\/.*)?$/));

          await dispatch("prefetchPageregions", { rewrites, locale, currentPath });
        }
        */

        commit("setInitialized", true);
        console.log("Prefetch completed!");
    } catch (err) {
      console.error("Errors on prefetch:", err);
    } finally {
      commit("setLoading", false);
    }
  },

  async prefetchPageregions({ commit, dispatch, state }, { rewrites, locale = null, currentPath = null }) {
    // reorder rewrites: first the one matching currentPath, then those matching locale, then the rest
    rewrites.sort((a, b) => {
        if (currentPath) {
          if (a.url === currentPath) return -1;
          if (b.url === currentPath) return 1;
        }
        if (a.locale === locale && b.locale !== locale) return -1;
        if (b.locale === locale && a.locale !== locale) return 1;
        return 0;
    });

    // fetch pageregions for the first rewrite immediately, then stagger the rest
    if (rewrites.length > 0) {
        await dispatch("pageregions/fetchPageregions", { param: rewrites[0].id }, { root: true });

        window.setTimeout(() => {
          rewrites.slice(1).forEach(rewrite => {
              dispatch("pageregions/fetchPageregions", { param: rewrite.id }, { root: true });
          });
        }, 250);
    }
  }

};

export default {
  namespaced: true,
  state,
  mutations,
  actions
};
