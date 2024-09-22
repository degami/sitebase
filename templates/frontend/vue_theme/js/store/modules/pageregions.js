import gql from 'graphql-tag';

const PAGEREGIONS_QUERY = gql`
query PageRegions ($rewriteId: Int!) {
    pageRegions(rewrite_id: $rewriteId) {
        after_body_open
        before_body_close
        pre_menu
        post_menu
        pre_header
        post_header
        pre_content
        post_content
        pre_footer
        post_footer
    }
}
`

const state = () => ({
    pageregions: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setPageregions(state, {rewriteId, pageregions}) {
        state.pageregions[rewriteId] = pageregions;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchPageregions({ commit, dispatch }, rewriteId) {
        const PAGEREGIONS_VARIABLES = {"rewriteId": rewriteId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }
            const { data } = await client.query({
                query: PAGEREGIONS_QUERY,
                variables: PAGEREGIONS_VARIABLES,
            });
            const pageRegions = data.pageRegions;
            commit('setPageregions', {rewriteId, pageregions: pageRegions});
        } catch (error) {
            console.error('Errore durante il fetch delle regioni:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async getPageregion({ state, dispatch }, {rewriteId, region}) {
        if (!state.pageregions[rewriteId]) {
            await dispatch('fetchPageregions', rewriteId);
        }

        return state.pageregions[rewriteId][region] || null;
    },
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
