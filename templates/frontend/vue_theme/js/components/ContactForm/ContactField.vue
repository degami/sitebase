<template>
  <div v-if="!isCaptcha" class="form-item" :class="`${field.field_type}-container`">
    <label :for="field.field_label">
      {{ translatedTitle }}
      <span v-if="field.field_required">*</span>
    </label>

    <input
      v-if="['textfield','email','number','file'].includes(field.field_type)"
      :type="fieldTypeInput"
      v-model="inputValue"
      :id="field.field_label"
      class="form-control"
    />

    <textarea
      v-else-if="field.field_type === 'textarea'"
      v-model="inputValue"
      :id="field.field_label"
      class="form-control"
    ></textarea>

    <select
      v-else-if="field.field_type === 'select'"
      v-model="inputValue"
      :id="field.field_label"
      class="form-control"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>

    <div v-else-if="field.field_type === 'checkbox'">
      <input type="checkbox" v-model="inputValue" :id="field.field_label" />
    </div>

    <div v-else-if="field.field_type === 'radios'">
      <div v-for="opt in options" :key="opt.value">
        <input type="radio" :id="opt.value" :value="opt.value" v-model="inputValue" />
        <label :for="opt.value">{{ opt.label }}</label>
      </div>
    </div>

    <div v-else-if="field.field_type === 'checkboxes'">
      <div v-for="opt in options" :key="opt.value">
        <input type="checkbox" :id="opt.value" :value="opt.value" v-model="inputValue" />
        <label :for="opt.value">{{ opt.label }}</label>
      </div>
    </div>

    <!-- Aggiungi qui altri tipi di campo come range, date, datetime, time, timeselect -->

    <div v-if="error" class="text-danger">{{ error }}</div>
  </div>
</template>

<script>
export default {
  props: {
    field: Object,
    modelValue: [String, Number, Array, Boolean],
    error: String
  },
  emits: ['update:modelValue'],
  data() {
    return {
      translatedTitle: '',
      options: [],
    };
  },
  computed: {
    inputValue: {
      get() { return this.modelValue },
      set(val) { this.$emit('update:modelValue', val) }
    },
    fieldTypeInput() {
      return this.field.field_type === 'textfield' ? 'text' : this.field.field_type;
    },
    isCaptcha() {
      return ['math_captcha','image_captcha','recaptcha'].includes(this.field.field_type);
    }
  },
  async mounted() {
    const rawTitle = JSON.parse(this.field.field_data)?.title || this.field.field_label;
    this.translatedTitle = await this.$store.dispatch('appState/translate', { text: rawTitle });

    if (['select','radios','checkboxes'].includes(this.field.field_type)) {
      try {
        const parsed = JSON.parse(this.field.field_data);
        this.options = parsed.options || [];
      } catch(e) {
        console.warn('Error parsing field_data options', e);
        this.options = [];
      }
    }
  }
}
</script>
