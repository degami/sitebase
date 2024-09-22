<template>
  <div class="example-component">
    <h1>{{ message }}</h1>
  </div>

  <p v-if="error">Something went wrong...</p>
  <p v-if="loading">Loading...</p>
  <div v-else v-for="config in configuration" :key="config.website.id">
    <strong>{{ config.website.site_name }} ({{  config.website.domain }})</strong>
    <span>locale: {{ config.locale }}</span>
    <h4>configs</h4>
    <ul>
      <li v-for="configEntry in config.configs">
        {{ configEntry.path }}: {{ configEntry.value }}
      </li>
    </ul>
  </div>

</template>

<script>
import { mapState } from 'vuex';

export default {
  computed: {
    // Correzione: Usa `mapState` all'interno di una proprietà computata come oggetto
    ...mapState('configuration', ['configuration', 'loading']),
  },
  created() {
    this.$store.dispatch('configuration/fetchConfiguration');
  },
  data() {
    return {
      message: 'Hello, this is another component!'
    };
  }
};
</script>

<style scoped lang="scss">
.example-component {
  h1 {
    color: red;
    font-size: 2em;
  }
}
</style>
