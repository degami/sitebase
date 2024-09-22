import gql from 'graphql-tag';

const TERM_QUERY = gql`
query Taxonomy ($termId: String!) {
    taxonomy(
        input: {
            criteria: [{ key: "id", value: $termId }]
            limit: 1
            offset: 0
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        id
        title
        content
        locale
        url
        meta_title
    }
}
`

const TERMS_LIST_QUERY = gql`
query Taxonomy ($input: SearchCriteriaInput) {
    taxonomy(input: $input) {
        id
        title
        content
        locale
        url
        meta_title
    }
}
`

const state = () => ({
    terms: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setTerms(state, terms) {
        terms.forEach(element => {
            state.terms = { ...state.terms, [element.id]: element };
        });
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchTerm({ commit, dispatch }, termId) {
        if (undefined !== state.news && undefined !== state.news[newsId]) {
            console.log("got term "+termId);
            return;
        }

        const TERM_VARIABLES = {"termId": termId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: TERM_QUERY,
                variables: TERM_VARIABLES,
            });
            commit('setTerms', data.taxonomy);
        } catch (error) {
            console.error('Errore durante il fetch del termine di tassonomia:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
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
            commit('setTerms', data.taxonomy);
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
