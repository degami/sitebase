<template>
    <div v-if="termsLoading">
      <Loader text="Loading terms..." />
    </div>
    <div v-else>
      <h1 class="taxonomy-title">{{ terms[id]?.title }}</h1>
      <div class="taxonomy-content" v-html="terms[id]?.content"></div>
    </div>
</template>
  
<script>
  import { mapState } from 'vuex';
  import Loader from '../utils/Loader.vue';

  export default {
    components: {
      Loader
    },
    data() {
      return {
        id: this.$route.params.id, // Imposta l'id iniziale dai parametri della rotta
      };
    },
    created() {
      this.$store.dispatch('configuration/fetchConfiguration');
      this.updateTaxonomyContent(this.$route.params.id);
    },
    computed: {
      ...mapState('configuration', {
        configLoading: 'loading', // loading per configuration
        configuration: 'configuration'
      }),
      ...mapState('terms', {
        termsLoading: 'loading', // loading per terms
        terms: 'terms'
      })
    },
    mounted() {
      this.$emit('data-sent', {term_id: this.$route.params.id});
    },
    watch: {
      '$route.params.id': function (newId) {
        this.updateTaxonomyContent(newId);
      }
    },
    methods: {
      updateTaxonomyContent(id) {
        this.$data.id = id;
        this.$store.dispatch('terms/fetchTerm', this.$data.id);
      },
    }
  };
  </script>