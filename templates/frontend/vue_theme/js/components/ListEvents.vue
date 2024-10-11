<template>

  <div v-if="eventsLoading">
    <Loader text="Loading events..." />
  </div>
  <div v-else>
    <h1 class="page-title">{{ title }}</h1>
    <div class="row">
        <div class="col-md-12">
            <ul class="event-list">
                <li v-for="eventsItem in events" :key="eventsItem.id">
                    <div class="event-detail">
                        <router-link :to="eventsItem.rewrite.url" class="news-title">{{ eventsItem.title }}</router-link>
                        &nbsp;<span class="event-date">{{ eventsItem.date }}</span>
                    </div>
                    <div class="event-description">{{ summarize(eventsItem.content, 20) }}</div>
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
    this.fetchEventsList(this.currentPage);
    this.setTitle();
  },
  computed: {
    ...mapState('configuration', {
      configLoading: 'loading', // loading per configuration
      configuration: 'configuration'
    }),
    ...mapState('events', {
      eventsLoading: 'loading', // loading per events
      events: 'events',
      totalCount: 'totalCount'
    })
  },
  watch: {
    '$route.query.page': function(newPage) {
      // Aggiorna currentPage e fetch
      this.currentPage = parseInt(newPage) || 0;
      this.fetchEventsList(this.currentPage);
    }
  },
  methods: {
    async setTitle() {
      this.title = await this.translate('Events'); // Attendi la traduzione e aggiorna il titolo
    },
    fetchEventsList(page = 0) {
        this.$store.dispatch('events/flushEvents');
        this.$store.dispatch('events/fetchAllEvents', {
            criteria: [
              {key: 'website_id', value: ""+this.$store.getters['appState/website_id']}, 
              {key: 'locale', value: ""+this.$store.getters['appState/locale']}
            ],
            offset: page * this.pageSize,
            limit: this.pageSize,
        });
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
    this.fetchEventsList(this.currentPage);
    next();
  }
}
</script>

<style lang="sass">
</style>