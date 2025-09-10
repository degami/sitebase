import gql from 'graphql-tag';

const MEDIA_QUERY = gql`
query Mediaelements ($mediaId: String!) {
    mediaelements(
        input: {
            criteria: [{ key: "id", value: $mediaId }]
            limit: 1
            offset: 0
            orderBy: [{ field: "id", direction: ASC }]
        }
    ) {
        items {
            id
            path
            filename
            mimetype
            filesize
            lazyload
            image_url
            thumb_url__300x200
            website {
                id
            }
        }
        count
    }
}
`

const MEDIA_LIST_QUERY = gql`
query Mediaelements ($input: SearchCriteriaInput) {
    mediaelements(input: $input) {
        items {
            id
            path
            filename
            mimetype
            filesize
            lazyload
            image_url
            thumb_url__300x200
            website {
                id
            }
        }
        count
    }
}
`

const state = () => ({
    mediaelements: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setMedias(state, mediaelements) {
        mediaelements.forEach(element => {
            state.mediaelements = { ...state.mediaelements, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushMedias(state) {
        state.mediaelements = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchMedia({ commit, dispatch, state }, {mediaId}) {
        if (undefined !== state.mediaelements && undefined !== state.mediaelements[mediaId]) {
            return state.mediaelements[mediaId];
        }

        const MEDIA_VARIABLES = {"mediaId": ""+mediaId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: MEDIA_QUERY,
                variables: MEDIA_VARIABLES,
            });
            commit('setMedias', data.mediaelements.items);
            commit('setTotalCount', data.mediaelements.count);
            returnElement = data.mediaelements.items[0];
        } catch (error) {
            console.error('Errore durante il fetch del media:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllMedias({ commit, dispatch }, {filters = null}) {
        const MEDIA_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: MEDIA_LIST_QUERY,
                variables: MEDIA_VARIABLES,
            });
            commit('setMedias', data.mediaelements.items);
            commit('setTotalCount', data.mediaelements.count);
        } catch (error) {
            console.error('Errore durante il fetch dei media:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    flushMedias({commit}) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch
        commit('flushMedias');
        commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
