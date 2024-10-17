import gql from 'graphql-tag';

const PAGEREGIONS_QUERY = gql`
query PageRegions ($rewriteId: Int!) {
    pageRegions(rewrite_id: $rewriteId) {
        locale
        regions {
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
}
`

const state = () => ({
    pageregions: {},
    loading: {},  // Aggiungi la proprietà loading
});
  
const mutations = {
    setPageregions(state, {rewriteId, pageregions}) {
        state.pageregions[rewriteId] = pageregions;

        state.pageregions[rewriteId] = {
            'after_body_open': pageregions.after_body_open,
            'before_body_close': pageregions.before_body_close,
            'pre_menu': pageregions.pre_menu,
            'post_menu': pageregions.post_menu,
            'pre_header': pageregions.pre_header,
            'post_header': pageregions.post_header,
            'pre_content': pageregions.pre_content,
            'post_content': pageregions.post_content,
            'pre_footer': pageregions.pre_footer,
            'post_footer': pageregions.post_footer,    
        };
    },
    setLoading(state, {rewriteId, loading}) {
        state.loading[rewriteId] = loading;
    },
};
  
const actions = {
    async fetchPageregions({ state, commit, dispatch }, {rewriteId}) {
        /*
        if (true === state.loading[rewriteId] || Object.keys(state.pageregions[rewriteId] || {}).length !== 0) {
            console.log('already fetched/fetching')
            return;
        }
        */
        const PAGEREGIONS_VARIABLES = {"rewriteId": rewriteId};

        commit('setLoading', {rewriteId, loading: true});

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }
            const { data } = await client.query({
                query: PAGEREGIONS_QUERY,
                variables: PAGEREGIONS_VARIABLES,
            });
            commit('setPageregions', {rewriteId, pageregions: data.pageRegions.regions});
        } catch (error) {
            console.error('Errore durante il fetch delle regioni:', error);
        } finally {
            commit('setLoading', { rewriteId, loading: false });  // Imposta loading a false quando il fetch è completato
        }
    },
    async getPageregion({ state, dispatch }, {rewriteId, region}) {
        if (!state.pageregions[rewriteId]) {
            await dispatch('fetchPageregions', {rewriteId});
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
