<template>
    <div v-if="termsLoading">
      <Loader text="Loading terms..." />
    </div>
    <div v-else>
      <h1 class="taxonomy-title">{{ terms[id]?.title }}</h1>
      <div class="taxonomy-content" v-html="terms[id]?.content"></div>

      <div v-if="terms[id].pages" class="taxonomy-pages">
          <ul class="list">
            <li v-for="pagesItem in terms[id].pages" key="pagesItem.id">
              <router-link :to="pagesItem.rewrite.url">{{ pagesItem.title }}</router-link>
            </li>
          </ul>
      </div>
    </div>
</template>
  
<script>
  import { mapState } from 'vuex';
  import Loader from '../utils/Loader.vue';

  export default {
    components: {
      Loader
    },
    props: {
      id: {
        type: Number,
        required: true
      },
      locale: {
        type: String,
        required: true
      }
    },
    data() {
    },
    created() {
      this.$store.dispatch('configuration/fetchConfiguration');
      this.updateTaxonomyContent(this.id);
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
      this.$emit('data-sent', {term_id: this.id});
    },
    watch: {
      '$route.params.id': function (newId) {
        this.updateTaxonomyContent(newId);
      }
    },
    methods: {
      updateTaxonomyContent(id) {
        const termItem = this.$store.dispatch('terms/fetchTerm', {termId: id});
        this.$emit('data-sent', {term_id: termItem.id});
      },
    }
  };
  </script>