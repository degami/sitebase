import gql from 'graphql-tag';

const EVENT_QUERY = gql`
query Events ($eventId: String!) {
    events(
        input: {
            criteria: [{ key: "id", value: $eventId }]
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
            date
            latitude
            longitude
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

const EVENTS_LIST_QUERY = gql`
query Events ($input: SearchCriteriaInput) {
    events(input: $input) {
        items {
            id
            title
            content
            locale
            url
            date
            latitude
            longitude
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
    events: {},
    totalCount: 0,
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setEvents(state, events) {
        events.forEach(element => {
            state.events = { ...state.events, [element.id]: element };
        });
    },
    setTotalCount(state, totalCount) {
        state.totalCount = totalCount;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
    flushEvents(state) {
        state.events = {};
        state.totalCount = 0;
    }
};
  
const actions = {
    async fetchEvent({ commit, dispatch, state }, {eventId}) {
        if (undefined !== state.events && undefined !== state.events[eventId]) {
            return state.events[eventId];
        }

        const EVENT_VARIABLES = {"eventId": ""+eventId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        let returnElement = null;
        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: EVENT_QUERY,
                variables: EVENT_VARIABLES,
            });
            commit('setEvents', data.events.items);
            commit('setTotalCount', data.events.count);
            returnElement = data.events.items[0];
        } catch (error) {
            console.error('Errore durante il fetch dell\'evento :', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }

        return returnElement;
    },
    async fetchAllEvents({ commit, dispatch }, {filters = null}) {
        const EVENTS_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: EVENTS_LIST_QUERY,
                variables: EVENTS_VARIABLES,
            });
            commit('setEvents', data.events.items);
            commit('setTotalCount', data.events.count);
        } catch (error) {
            console.error('Errore durante il fetch degli eventi:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    flushEvents({commit}) {
        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch
        commit('flushEvents');
        commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
