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
        id
        title
        content
        locale
        url
        meta_title
        date
        latitude
        longitude
    }
}
`

const EVENTS_LIST_QUERY = gql`
query Events ($input: SearchCriteriaInput) {
    events(input: $input) {
        id
        title
        content
        locale
        url
        meta_title
        date
        latitude
        longitude
    }
}
`

const state = () => ({
    events: {},
    loading: false,  // Aggiungi la proprietà loading
});
  
const mutations = {
    setEvents(state, events) {
        events.forEach(element => {
            state.events = { ...state.events, [element.id]: element };
        });
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchEvent({ commit, dispatch }, eventId) {
        if (undefined !== state.events && undefined !== state.events[eventId]) {
            console.log("got events "+eventId);
            return;
        }

        const EVENT_VARIABLES = {"eventId": eventId};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: EVENT_QUERY,
                variables: EVENT_VARIABLES,
            });
            commit('setEvents', data.events);
        } catch (error) {
            console.error('Errore durante il fetch dell\'evento :', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async fetchAllEvents({ commit, dispatch }, filters = nul) {
        const EVENTS_VARIABLES = {"input": filters};

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
                throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: EVENTES_LIST_QUERY,
                variables: EVENTS_VARIABLES,
            });
            commit('setEvents', data.events);
        } catch (error) {
            console.error('Errore durante il fetch degli eventi:', error);
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
