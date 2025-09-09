import gql from 'graphql-tag';

const LINKS_QUERY = gql`
query Linkexchanges ($linkId: String!) {
    linkexchanges(
        input: {
            criteria: [{ key: "id", value: $linkId }]
            limit: 1
            offset: 0
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        items {
            id
            url
            title
            locale
            description
            domain
            active
            website {
                id
            }
        }
        count
    }
}
`

const LINKS_LIST_QUERY = gql`
query Linkexchanges ($input: SearchCriteriaInput) {
    linkexchanges(input: $input) {
        items {
            id
            url
            title
            locale
            description
            domain
            active
            website {
                id
            }
        }
        count
    }
}
`

const LINKS_MUTATION = gql`
mutation SubmitLinkExchange ($url:String!, $email:String!, $title: String!, $description: String! ){
    submitLinkExchange(url: $url, email: $email, title: $title, description: $description) {
        success
        message
    }
}
`

const state = () => ({
    linkexchanges: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setLinks(state, linkexchanges) {
        linkexchanges.forEach(element => {
            state.linkexchanges = { ...state.linkexchanges, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushLinks(state) {
        state.linkexchanges = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchLink({ commit, dispatch }, {linkId}) {
        if (undefined !== state.links && undefined !== state.links[linkId]) {
            console.log("got link "+linkId);
            return state.links[linkId];
        }

        const LINKS_VARIABLES = {"linkId": ""+linkId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: LINKS_QUERY,
                variables: LINKS_VARIABLES,
            });
            commit('setLinks', data.linkexchanges.items);
            commit('setTotalCount', data.linkexchanges.count);
            returnElement = data.linkexchanges.items[0];
        } catch (error) {
            console.error('Errore durante il fetch del link:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllLinks({ commit, dispatch }, {filters = null}) {
        const LINKS_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: LINKS_LIST_QUERY,
                variables: LINKS_VARIABLES,
            });
            commit('setLinks', data.linkexchanges.items);
            commit('setTotalCount', data.linkexchanges.count);
        } catch (error) {
            console.error('Errore durante il fetch dei links:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async submitLink({ commit, dispatch }, {url, email, title, description}) {
        const LINKS_VARIABLES = {
            "url": url,
            "email": email,
            "title": title,
            "description": description
        };

        //commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.mutate({
                mutation: LINKS_MUTATION,
                variables: LINKS_VARIABLES,
            });

            return data.submitLinkExchange || null;
        } catch (error) {
            console.error('Errore durante il submit del link:', error);
        } finally {
            //commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    flushLinks({commit}) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch
        commit('flushLinks');
        commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
