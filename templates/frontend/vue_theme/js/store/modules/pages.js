import gql from 'graphql-tag';

const PAGE_QUERY = gql`
query Pages ($pageId: String!) {
    pages(
        input: {
            criteria: [{ key: "id", value: $pageId }]
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
            gallery {
                image_url
                thumb_url__300x200
                lazyload
                mimetype
                filesize
                filename
            }
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

const PAGES_LIST_QUERY = gql`
query Pages ($input: SearchCriteriaInput) {
    pages(input: $input) {
        items {
            id
            title
            content
            locale
            url
            meta_title
            gallery {
                getImageUrl
                getThumbUrl__300x200
                lazyload
                mimetype
                filesize
                filename
            }
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
    pages: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setPages(state, pages) {
        pages.forEach(element => {
            state.pages = { ...state.pages, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushPages(state) {
        state.pages = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchPage({ commit, dispatch }, {pageId}) {
        if (undefined !== state.pages && undefined !== state.pages[pageId]) {
            console.log("got page "+pageId);
            return;
        }

        const PAGE_VARIABLES = {"pageId": ""+pageId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: PAGE_QUERY,
                variables: PAGE_VARIABLES,
            });
            commit('setPages', data.pages.items);
            commit('setTotalCount', data.pages.count);
            returnElement = data.pages.items[0];
        } catch (error) {
            console.error('Errore durante il fetch della pagina:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllPages({ commit, dispatch }, {filters = null}) {
        const PAGE_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: PAGES_LIST_QUERY,
                variables: PAGE_VARIABLES,
            });
            commit('setPages', data.pages.items);
            commit('setTotalCount', data.pages.count);
        } catch (error) {
            console.error('Errore durante il fetch delle pagine:', error);
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
