import gql from 'graphql-tag';

const MEDIA_QUERY = gql`
query Medias ($mediaId: String!) {
    medias(
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
            getImageUrl
            getThumbUrl_300x200
            website {
                id
            }
        }
        count
    }
}
`

const MEDIA_LIST_QUERY = gql`
query Medias ($input: SearchCriteriaInput) {
    medias(input: $input) {
        items {
            id
            path
            filename
            mimetype
            filesize
            lazyload
            getImageUrl
            getThumbUrl_300x200
            website {
                id
            }
        }
        count
    }
}
`

const state = () => ({
    medias: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setMedias(state, medias) {
        medias.forEach(element => {
            state.medias = { ...state.medias, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushMedias(state) {
        state.medias = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchMedia({ commit, dispatch }, mediaId) {
        if (undefined !== state.medias && undefined !== state.medias[mediaId]) {
            console.log("got media "+mediaId);
            return state.medias[mediaId];
        }

        const MEDIA_VARIABLES = {"mediaId": mediaId};

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
            commit('setMedias', data.medias.items);
            commit('setTotalCount', data.medias.count);
            returnElement = data.medias.items[0];
        } catch (error) {
            console.error('Errore durante il fetch del media:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllMedias({ commit, dispatch }, filters = null) {
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
            commit('setMedias', data.medias.items);
            commit('setTotalCount', data.medias.count);
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
