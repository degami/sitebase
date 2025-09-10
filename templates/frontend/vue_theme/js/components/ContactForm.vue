<template>
    <div v-if="message.text" :class="`mt-2 alert ${message.success ? 'alert-success' : 'alert-danger'}`">
        {{ message.text }}
    </div>

    <form @submit.prevent="submitForm">
        <input type="hidden" :value="contact.id" name="contact_id" />

        <div id="fieldset-contactfields" class="form-control tagcontainer">
            <ContactField
            v-for="field in contact.contact_definition"
            :key="field.id"
            :field="field"
            v-model="formData[field.field_label]"
            :error="errors[field.field_label]"
            />
        </div>

        <div class="form-item mt-3 button-container">
            <button type="submit" class="btn btn-primary btn-lg btn-block" :disabled="loading">
                <span v-if="submitting">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    {{ labels.sending }}
                </span>
                <span v-else>
                    {{ labels.send }}
                </span>
            </button>
        </div>
    </form>
</template>

<script>
import ContactField from './ContactForm/ContactField.vue';
import { mapActions } from 'vuex';

export default {
  components: { ContactField },
  props: { contact: Object },
  data() {
    const initialForm = {};
    if (this.contact && this.contact.contact_definition) {
      this.contact.contact_definition.forEach(f => {
        initialForm[f.field_label] = '';
      });
    }
    return {
      formData: initialForm,
      errors: {},
      loading: false,
      message: { text: '', success: false },
      labels: {
        send: '',
        sending: ''
      },
      submitting: false,
    };
  },
  created() {
    this.setTitle();
  },
  methods: {
    ...mapActions('contacts', ['submitContact']),

    async setTitle() {
      this.labels.send = await this.translate('Send');
      this.labels.sending = await this.translate('Sending...');
    },
    async translate(text) {
      return this.$store.dispatch('appState/translate', {text});
    },

    validateFields() {
        console.log('formData corrente:', this.formData);
        this.errors = {};
        let valid = true;
        this.contact.contact_definition
            .filter(f => !['math_captcha','image_captcha','recaptcha'].includes(f.field_type))
            .forEach(field => {
                const val = this.formData[field.field_label];
                if (field.field_required && (!val || val.toString().trim() === '')) {
                this.errors[field.field_label] = 'Campo obbligatorio';
                valid = false;
                }
            });
        return valid;
    },

    async submitForm() {
        if (!this.validateFields()) return;

        this.submitting = true;
        this.message = { text: '', success: false };

        const submissionData = this.contact.contact_definition
            .filter(f => !['math_captcha','image_captcha','recaptcha'].includes(f.field_type))
            .map(field => ({
                contact_definition_id: field.id,
                field_value: this.formData[field.field_label] || ''
            }));

        try {
            const result = await this.submitContact({
                contactId: this.contact.id,
                submissionData
            });

            this.message = { text: result.message, success: result.success };
            if (result.success){
                this.formData = {}
            }  else {
                this.message = { text: result.message, success: false };
            }
        } catch (err) {
            console.error(err);
            this.message = { text: 'Errore durante l\'invio del form.', success: false };
        } finally {
            this.submitting = false;
        }
    },
  }
};
</script>
