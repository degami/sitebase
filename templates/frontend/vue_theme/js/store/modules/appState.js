const state = {
    website_id: null,
    locale: null,
};

const mutations = {
    SET_WEBSITE_ID(state, website_id) {
        state.website_id = website_id;
    },
    SET_LOCALE(state, locale) {
        state.locale = locale;
    },
};

const actions = {
    updateLocale({ commit }, locale) {
        commit('SET_LOCALE', locale);
    },
    updateWebsiteId({ commit }, website_id) {
        commit('SET_WEBSITE_ID', website_id);
    },
};

const getters = {
    website_id: (state) => state.website_id,
    locale: (state) => state.locale,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};