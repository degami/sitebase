<template>
  <div class="content">
    <h1 class="page-title">{{ contact?.title }}</h1>
    <div class="row">
      <div class="col-6">
        <div class="page-content" v-html="contact?.content"></div>
      </div>
      <div class="col-6">
        <div class="contact-form">
          <Loader v-if="loading" text="Caricamento contatto..." />
          <ContactForm v-else :contact="contact" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';
import Loader from '../utils/Loader.vue';
import ContactForm from './ContactForm.vue';

export default {
  components: { Loader, ContactForm },
  props: {
    id: { type: Number, required: true },
    locale: { type: String, required: true }
  },
  data() {
    return { loading: true };
  },
  computed: {
    ...mapState('contacts', {
      contacts: state => state.contacts
    }),
    contact() {
      return this.contacts[this.id] || null;
    }
  },
  async created() {
    try {
      this.loading = true;
      await this.$store.dispatch('contacts/fetchContact', { contactId: this.id, locale: this.locale });
    } finally {
      this.loading = false;
    }
  }
};
</script>
