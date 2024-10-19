import gql from 'graphql-tag';

const TERM_QUERY = gql`
query Terms ($termId: String!) {
    terms(
        input: {
            criteria: [{ key: "id", value: $termId }]
            limit: 1
            offset: 0
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        items {
            id
            title
            content
            locale
            url
            meta_title
            rewrite {
                id
                url
                route
                locale
            }
        }
        count
    }
}
`

const TERMS_LIST_QUERY = gql`
query Terms ($input: SearchCriteriaInput) {
    terms(input: $input) {
        items {
            id
            title
            content
            locale
            url
            meta_title
            rewrite {
                id
                url
                route
                locale
            }
        }
        count
    }
}
`

const state = () => ({
    terms: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setTerms(state, terms) {
        terms.forEach(element => {
            state.terms = { ...state.terms, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushTerms(state) {
        state.terms = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchTerm({ commit, dispatch }, termId) {
        if (undefined !== state.news && undefined !== state.news[newsId]) {
            console.log("got term "+termId);
            return;
        }

        const TERM_VARIABLES = {"termId": termId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: TERM_QUERY,
                variables: TERM_VARIABLES,
            });
            commit('setTerms', data.terms.items);
            commit('setTotalCount', data.terms.count);
            returnElement = data.terms.items[0];
        } catch (error) {
            console.error('Errore durante il fetch del termine di tassonomia:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllTerms({ commit, dispatch }, filters = nul) {
        const TERMS_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: TERMS_LIST_QUERY,
                variables: TERMS_VARIABLES,
            });
            commit('setTerms', data.terms.items);
            commit('setTotalCount', data.terms.count);
        } catch (error) {
            console.error('Errore durante il fetch dei termini di tassonoia:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};