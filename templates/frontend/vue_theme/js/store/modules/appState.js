import gql from 'graphql-tag';

const TRANSLATIONS_QUERY = gql`
query Translations {
    Translations {
        key
        value
    }
}
`

const state = {
    website_id: null,
    locale: null,
    translations: {},
};

const mutations = {
    setLoading(state, loading) {
        state.loading = loading;
    },
    SET_WEBSITE_ID(state, website_id) {
        state.website_id = website_id;
    },
    SET_LOCALE(state, locale) {
        state.locale = locale;
    },
    SET_TRANSLATIONS(state, translations) {
        translations.forEach(element => {
            state.translations = { ...state.translations, [element.key]: element.value };
        });
    },
    FLUSH_TRANSLATIONS(state) {
        state.translations = {};
    }
};

const actions = {
    updateLocale({ commit, dispatch }, locale) {
        commit('SET_LOCALE', locale);
        dispatch('apolloClient/updateLocale', locale);
    },
    updateWebsiteId({ commit }, website_id) {
        commit('SET_WEBSITE_ID', website_id);
    },
    async fetchTranslations({ commit, dispatch }) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: TRANSLATIONS_QUERY,
            });
            commit('SET_TRANSLATIONS', data.Translations);
        } catch (error) {
            console.error('Errore durante il fetch delle traduzioni:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    flushTranslations({commit}) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch
        commit('FLUSH_TRANSLATIONS');
        commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
    },
    translate({state}, {text, parameters = []}) {
        function sprintf(format, ...args) {
            if (typeof format !== 'string') {
                throw new Error('Il primo argomento deve essere una stringa');
            }

            let i = 0;

            return format.replace(/%[sd]/g, function(match) {
                if (match === "%s") {
                    return String(args[i++]);
                } else if (match === "%d") {
                    return Number(args[i++]);
                }
                return match;
            });
        }

        if (state.translations[text]) {
            text = state.translations[text];
        }

        return sprintf(text, ...parameters);
    }
};

const getters = {
    website_id: (state) => state.website_id,
    locale: (state) => state.locale,
    translations: (state) => state.translations,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};