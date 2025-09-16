import gql from 'graphql-tag';

const CONFIGURATION_QUERY = gql`
query Configuration {
    configuration {
        locale
        website {
            id
            site_name
            domain
            aliases
            default_locale
        }
        configs {
            path
            value
        }
    }
}
`

const state = () => ({
    configuration: [],
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setConfiguration(state, configuration) {
        state.configuration = configuration;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};

const actions = {
    async fetchConfiguration({ state, commit, dispatch }) {
        if (state.configuration?.length) {
            return;
        }

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: CONFIGURATION_QUERY
            });
            commit('setConfiguration', data.configuration);
        } catch (error) {
            console.error('Errore durante il fetch della configurazione:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async getConfigurationByPath({ state, dispatch }, { path, locale, websiteId = null, siteDomain = null }) {
        if (locale == 'en') {
            locale = null;
        }
        if (!state.configuration.length) {
            await dispatch('fetchConfiguration');
        }

        let configSite = null;
        if (null !== websiteId) {
            configSite = state.configuration.find(config => 
                config.website.id === websiteId && config.locale === locale
            );
        } else if (null !== siteDomain) {
            configSite = state.configuration.find(config => 
                config.website.domain === siteDomain && config.locale === locale
            );
        }
        if (configSite) {
            const configEntry = configSite.configs.find(entry => entry.path === path);
            return configEntry ? configEntry.value : null;
        }
    
        return null;
    },
    async getWebsite({ state, dispatch }, {siteDomain, locale = null}) {
        if (!state.configuration.length) {
            await dispatch('fetchConfiguration');
        }

        const configSite = state.configuration.find(config => 
            config.website.domain === siteDomain && config.locale === locale
        );

        if (configSite) {
            return configSite.website;
        }
    
        return null;
    },
    async getWebsiteId({ state, getters, rootState, rootGetters, commit, dispatch }, {siteDomain, locale = null}) {
        const website = await dispatch('getWebsite', {siteDomain, locale});

        if (!rootGetters['appState/website_id'] && website?.id) {
            // if appstate website_id is not set, set it now
            await dispatch('appState/updateWebsiteId', website.id, { root: true });
        }

        if (website) {
            return website.id;
        }
    
        return null;
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
