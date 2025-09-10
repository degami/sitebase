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
  async prefetchAll({ commit, dispatch, state }) {
    if (state.initialized) {
      console.log("Prefetch già fatto");
      return;
    }

    commit("setLoading", true);
    try {
      // richiami entrambi i moduli in parallelo
      await Promise.all([
        dispatch("pages/fetchAllPages", { filters: null }, { root: true }),
        dispatch("terms/fetchAllTerms", { filters: null, maxLevels: 2 }, { root: true })
      ]);

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
