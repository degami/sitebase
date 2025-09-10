import gql from 'graphql-tag';

const CONTACTS_QUERY = gql`
query Contacts ($contactId: String!, $locale: String!) {
  contacts(
    input: {
      criteria: [{ key: "id", value: $contactId }, { key: "locale", value: $locale }]
      limit: 1
    }
  ) {
    items {
      id
      title
      content
      contact_definition {
        id
        contact_id
        field_type
        field_label
        field_required
        field_data
      }
    }
    count
  }
}
`;

const SUBMIT_CONTACT_MUTATION = gql`
mutation SubmitContact($contactId: Int!, $submissionData: [SubmitContactFieldValue!]) {
  submitContact(contact_id: $contactId, submission_data: $submissionData) {
    success
    message
  }
}
`;

const state = () => ({
  contacts: {},
  totalCount: 0,
  loading: false,
});

const mutations = {
  setContacts(state, contacts) {
    contacts.forEach(item => {
      state.contacts = { ...state.contacts, [item.id]: item };
    });
  },
  setTotalCount(state, totalCount) {
    state.totalCount = totalCount;
  },
  setLoading(state, loading) {
    state.loading = loading;
  },
  flushContacts(state) {
    state.contacts = {};
    state.totalCount = 0;
  }
};

const actions = {
  async fetchContact({ commit, dispatch, state }, { contactId, locale }) {
    if (state.contacts[contactId]) {
      console.log("got contact " + contactId);
      return state.contacts[contactId];
    }

    const VARIABLES = { contactId: "" + contactId, locale };

    commit('setLoading', true);
    let returnElement = null;

    try {
      const client = await dispatch('apolloClient/getApolloClient', null, { root: true });
      if (!client) throw new Error("Apollo Client non inizializzato");

      const { data } = await client.query({
        query: CONTACTS_QUERY,
        variables: VARIABLES
      });

      commit('setContacts', data.contacts.items);
      commit('setTotalCount', data.contacts.count);

      returnElement = data.contacts.items[0];
    } catch (error) {
      console.error('Errore durante il fetch del contatto:', error);
    } finally {
      commit('setLoading', false);
    }

    return returnElement;
  },
  async submitContact({ dispatch }, { contactId, submissionData }) {
    try {
      const client = await dispatch('apolloClient/getApolloClient', null, { root: true });
      if (!client) throw new Error("Apollo Client non inizializzato");

      const { data } = await client.mutate({
        mutation: SUBMIT_CONTACT_MUTATION,
        variables: { contactId, submissionData }
      });

      return data.submitContact; // { success: true/false, message: string }
    } catch (err) {
      console.error('Errore durante il submit del contatto:', err);
      return { success: false, message: err.message };
    }
  },
  flushContacts({ commit }) {
    commit('setLoading', true);
    commit('flushContacts');
    commit('setLoading', false);
  }
};

export default {
  namespaced: true,
  state,
  mutations,
  actions
};
