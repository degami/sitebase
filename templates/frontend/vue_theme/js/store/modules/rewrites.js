import gql from 'graphql-tag';

const REWRITES_QUERY = gql`
query Rewrites ($websiteId: String!) {
    rewrites(
        input: {
            criteria: [{ key: "website_id", value: $websiteId }]
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        items {
            id
            url
            route
            locale
            website {
                id
            }
        }
    }
}
`

const state = () => ({
    rewrites: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setRewrites(state, rewrites) {
        state.rewrites = rewrites;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchRewrites({ commit, dispatch }, {websiteId}) {
        const REWRITES_VARIABLES = {"websiteId": ""+websiteId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: REWRITES_QUERY,
                variables: REWRITES_VARIABLES,
            });
            commit('setRewrites', data.rewrites.items);
        } catch (error) {
            console.error('Errore durante il fetch delle rewrites:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },

    async findRewriteByRoute({state, dispatch}, {route, websiteId = null}) {
        if (!state.rewrites.length) {
            if (null == websiteId) {
                return null;
            }

            await dispatch('fetchRewrites', {websiteId});
        }

        for (let rewrite of state.rewrites) {
            if (rewrite.route == route) {
                return rewrite;
            }
        }

        return null;
    },

    async findRewriteByUrl({state, dispatch}, {url, websiteId = null}) {
        if (!state.rewrites.length) {
            if (null == websiteId) {
                return null;
            }

            await dispatch('fetchRewrites', {websiteId});
        }

        for (let rewrite of state.rewrites) {
            if (rewrite.url == url) {
                return rewrite;
            }
        }

        return null;
    },

    async findRewriteById({state, dispatch}, {rewriteId, websiteId = null}) {
        if (!state.rewrites.length) {
            if (null == websiteId) {
                return null;
            }

            await dispatch('fetchRewrites', {websiteId});
        }

        for (let rewrite of state.rewrites) {
            if (rewrite.id == rewriteId) {
                return rewrite;
            }
        }

        return null;
    },
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
