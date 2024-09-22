import gql from 'graphql-tag';

const NEWS_QUERY = gql`
query News ($newsId: String!) {
    news(
        input: {
            criteria: [{ key: "id", value: $newsId }]
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
        date
    }
}
`

const NEWS_LIST_QUERY = gql`
query News ($input: SearchCriteriaInput) {
    news(input: $input) {
        id
        title
        content
        locale
        url
        meta_title
        date
    }
}
`

const state = () => ({
    news: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setNews(state, news) {
        news.forEach(element => {
            state.news = { ...state.news, [element.id]: element };
        });
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchNews({ commit, dispatch }, newsId) {
        if (undefined !== state.news && undefined !== state.news[newsId]) {
            console.log("got news "+newsId);
            return;
        }

        const NEWS_VARIABLES = {"newsId": newsId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: NEWS_QUERY,
                variables: NEWS_VARIABLES,
            });
            commit('setNews', data.news);
        } catch (error) {
            console.error('Errore durante il fetch della news:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async fetchAllNews({ commit, dispatch }, filters = nul) {
        const NEWS_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: NEWS_LIST_QUERY,
                variables: NEWS_VARIABLES,
            });
            commit('setNews', data.news);
        } catch (error) {
            console.error('Errore durante il fetch delle news:', error);
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
