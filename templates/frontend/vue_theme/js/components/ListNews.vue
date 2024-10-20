<template>

  <div v-if="newsLoading">
    <Loader text="Loading news..." />
  </div>
  <div v-else>
    <h1 class="page-title">{{ title }}</h1>
    <div class="row">
        <div class="col-md-12">
            <ul class="news-list">
                <li v-for="newsItem in news" :key="newsItem.id">
                    <div class="news-detail">
                        <router-link :to="newsItem.rewrite.url" class="news-title">{{ newsItem.title }}</router-link>
                        &nbsp;<span class="news-date">{{ newsItem.date }}</span>
                    </div>
                    <div class="news-description">{{ summarize(newsItem.content, 20) }}</div>
                </li>
            </ul>
            <Paginator :current_page="currentPage" :total="totalCount" :page_size="pageSize" />        
        </div>
    </div>
  </div>

</template>

<script>
import { mapState } from 'vuex';
import Loader from '../utils/Loader.vue';
import Paginator from '../utils/Paginator.vue';

export default {
  components: {
    Loader,
    Paginator
  },
  data() {
    return {
      title: '',
      currentPage: parseInt(this.$route.query.page) || 0,
      pageSize: 20,
    }
  },
  created() {
    this.$store.dispatch('configuration/fetchConfiguration');
    this.fetchNewsList(this.currentPage);
    this.setTitle();
  },
  computed: {
    ...mapState('configuration', {
      configLoading: 'loading', // loading per configuration
      configuration: 'configuration'
    }),
    ...mapState('news', {
      newsLoading: 'loading', // loading per news
      news: 'news',
      totalCount: 'totalCount'
    })
  },
  watch: {
    '$route.query.page': function(newPage) {
      // Aggiorna currentPage e fetch
      this.currentPage = parseInt(newPage) || 0;
      this.fetchNewsList(this.currentPage);
    }
  },
  mounted() {
  },
  methods: {
    async setTitle() {
      this.title = await this.translate('News'); // Attendi la traduzione e aggiorna il titolo
    },
    fetchNewsList(page = 0) {
        this.$store.dispatch('news/flushNews');
        this.$store.dispatch('news/fetchAllNews', {filters: {
            criteria: [
              {key: 'website_id', value: ""+this.$store.getters['appState/website_id']}, 
              {key: 'locale', value: ""+this.$store.getters['appState/locale']}
            ],
            offset: page * this.pageSize,
            limit: this.pageSize,
        }});
    },
    summarize(text, maxWords = 10) {
        maxWords = Math.abs(parseInt(maxWords, 10));
        const words = text.replace(/<\/?[^>]+(>|$)/g, "").split(/\s+/);
        if (words.length < maxWords) {
            return text;
        }
        return words.slice(0, maxWords).join(" ") + ' ...';
    },
    async translate(text) {
      return this.$store.dispatch('appState/translate', {text});
    }
  },
  beforeRouteUpdate(to, from, next) {
    // Aggiorna currentPage se cambia la pagina nella query string
    this.currentPage = parseInt(to.query.page) || 0;
    this.fetchNewsList(this.currentPage);
    next();
  }
}
</script>

<style lang="sass">
</style>