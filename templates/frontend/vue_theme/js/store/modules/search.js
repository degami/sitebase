import gql from 'graphql-tag';

const SEARCH_QUERY = gql`
query Search ($input: String!, $locale: String, $page: Int) {
    search(input: $input, locale: $locale, page: $page) {
        search_query
        total
        page
        search_result {
            frontend_url
            title
            excerpt
        }
    }
}
`

const state = () => ({
    results: [],
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setResults(state, results) {
        state.results = results;
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushResults(state) {
        state.results = [];
        state.totalCount = 0;
    }
};
  
const actions = {
    async doSearch({ commit, dispatch }, {searchString, locale, page = 0}) {

        if (!searchString || searchString.trim() === '') {
            commit('flushResults');
            return [];
        }

        const SEARCH_VARIABLES = {
            "input": searchString, 
            "locale": locale,
            "page": page
        };

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: SEARCH_QUERY,
                variables: SEARCH_VARIABLES,
            });
            commit('setResults', data.search.search_result);
            commit('setTotalCount', data.search.total);
            returnElement = data.search.search_result;
        } catch (error) {
            console.error('Errore durante il fetch della ricerca:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    flushResults({commit}) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch
        commit('flushResults');
        commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
