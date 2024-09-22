<template>
  <div v-if="newsLoading">
    <Loader text="Loading news..." />
  </div>
  <div v-else>
    <h1 class="news-title">{{ news[id]?.title }}</h1>
    <div class="news-date" v-html="news[id]?.date"></div>
    <div class="news-content" v-html="news[id]?.content"></div>
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
    this.updateNewsContent(this.$route.params.id);
  },
  computed: {
    ...mapState('configuration', {
      configLoading: 'loading', // loading per configuration
      configuration: 'configuration'
    }),
    ...mapState('news', {
      newsLoading: 'loading', // loading per news
      news: 'news'
    })
  },
  mounted() {
    this.$emit('data-sent', {news_id: this.$route.params.id});
  },
  watch: {
    '$route.params.id': function (newId) {
      this.updateNewsContent(newId);
    }
  },
  methods: {
    updateNewsContent(id) {
      this.$data.id = id;
      this.$store.dispatch('news/fetchNews', this.$data.id);
    },
  }
};
</script>