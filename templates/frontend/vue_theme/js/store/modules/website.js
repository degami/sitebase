import gql from 'graphql-tag';

const WEBSITE_QUERY = gql`
query Website($siteDomain: String!) {
    websites(
        input: {
            criteria: [{ key: "domain", value: $siteDomain }]
            limit: 1
            offset: 0
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        items {
            id
            site_name
            domain
            aliases
            default_locale        
        }
        count
    }
}
`

const state = () => ({
    website: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setWebsite(state, website) {
        state.website = website;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};

const actions = {
    async getWebsite({ commit, dispatch }, {siteDomain}) {
        const WEBSITE_VARIABLES = {"siteDomain": siteDomain};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: WEBSITE_QUERY,
                variables: WEBSITE_VARIABLES,
            });
            commit('setWebsite', data.websites.items[0]);
            returnElement = data.websites.items[0];
        } catch (error) {
            console.error('Errore durante il fetch del website:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
