import gql from 'graphql-tag';

const PAGEREGIONS_REWRITE_QUERY = gql`
query PageRegions ($rewriteId: Int) {
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

const PAGEREGIONS_ROUTE_QUERY = gql`
query PageRegions ($routePath: String) {
    pageRegions(route_path: $routePath) {
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
    setPageregions(state, {key, pageregions}) {
        state.pageregions[key] = {
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
    setLoading(state, {key, loading}) {
        state.loading[key] = loading;
    },
};
  
const actions = {
    async fetchPageregions({state, commit, dispatch}, {param}) {
        /*
        if (true === state.loading[rewriteId] || Object.keys(state.pageregions[rewriteId] || {}).length !== 0) {
            console.log('already fetched/fetching')
            return;
        }
        */

        if (undefined == param) {
            return;
        }

        if (!isNaN(parseInt(param))) {
            return await dispatch('fetchPageregionsByRewrite', {rewriteId: param});
        } else {
            return await dispatch('fetchPageregionsByRoute', {routePath: param});
        }        
    },
    async fetchPageregionsByRewrite({state, commit, dispatch}, {rewriteId}) {
        const PAGEREGIONS_VARIABLES = {"rewriteId": parseInt(rewriteId)};

        if (state.pageregions[""+rewriteId]) { 
            // already fetched
            return state.pageregions[""+rewriteId];
        }

        commit('setLoading', { key: rewriteId, loading: true});

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }
            const { data } = await client.query({
                query: PAGEREGIONS_REWRITE_QUERY,
                variables: PAGEREGIONS_VARIABLES,
            });
            await commit('setPageregions', { key: ""+rewriteId, pageregions: data.pageRegions.regions });

            return state.pageregions[""+rewriteId];
        } catch (error) {
            //console.error('Errore durante il fetch delle regioni:', error);
        } finally {
            commit('setLoading', { key: rewriteId, loading: false });
        }
    },
    async fetchPageregionsByRoute({state, commit, dispatch}, {routePath}) {
        const PAGEREGIONS_VARIABLES = {"routePath": routePath};

        if (state.pageregions[""+routePath]) { 
            // already fetched
            return state.pageregions[""+routePath];
        }

        commit('setLoading', { key: routePath, loading: true});

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }
            const { data } = await client.query({
                query: PAGEREGIONS_ROUTE_QUERY,
                variables: PAGEREGIONS_VARIABLES,
            });
            await commit('setPageregions', { key: routePath, pageregions: data.pageRegions.regions });

            return state.pageregions[""+routePath];
        } catch (error) {
            //console.error('Errore durante il fetch delle regioni:', error);
        } finally {
            commit('setLoading', { key: routePath, loading: false });
        }
    },
    async getPageregion({state, dispatch}, {param, region}) {
        if (!param) {
            return null;
        }

        if (!state.pageregions[""+param]) {
            const pageRegions = await dispatch('fetchPageregions', { param: param });
            return pageRegions ? pageRegions[region] || null : null;
        }

        return state.pageregions[""+param][region] || null;
    },
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
