<template>

  <div v-if="linksLoading">
    <Loader text="Loading links..." />
  </div>
  <div v-else>
    <h1 class="page-title" v-html="title"></h1>
    <div class="row">
        <div class="col-md-8">
            <ul class="links_exchange">
                <li v-for="linksItem in links" :key="linksItem.id">
                    <a :href="linksItem.url" class="link-url">
                        <img
                        style="max-width: 1em; vertical-align: baseline;"
                        :src="linksItem.domain + '/favicon.ico'"
                        target="_blank"
                        >
                        {{ linksItem.url }}
                    </a>
                    <span class="link-title">{{ linksItem.url }}</span>
                    <span class="link-description">{{ linksItem.description }}</span>
                </li>
            </ul>
            <Paginator :current_page="currentPage" :total="totalCount" :page_size="pageSize" />        
        </div>
        <div class="col-md-4">
            <div class="contact-form">
                <h2 v-html="addlink_title"></h2>

                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
                <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

                <div class="contact-form">
                  <form @submit.prevent="submitForm">
                    <div class="form-group">
                      <label for="url">{{ labels.url }}</label>
                      <input v-model="form.url" type="text" id="url" class="form-control" required>
                    </div>

                    <div class="form-group">
                      <label for="email">{{ labels.email }}</label>
                      <input v-model="form.email" type="email" id="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                      <label for="title">{{ labels.title }}</label>
                      <input v-model="form.title" type="text" id="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                      <label for="description">{{ labels.description }}</label>
                      <textarea v-model="form.description" id="description" class="form-control" rows="5" required></textarea>
                    </div>

                    <div class="form-item mt-3">
                      <button 
                        type="submit" 
                        class="btn btn-primary btn-lg btn-block"
                        :disabled="submitting">
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
                </div>

            </div>
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
  props: {
    locale: {
        type: String,
        required: false
      }
  },
  data() {
    return {
      title: '',
      addlink_title: '',
      currentPage: parseInt(this.$route.query.page) || 0,
      pageSize: 20,
      form: {
        url: '',
        email: '',
        title: '',
        description: ''
      },
      successMessage: '',
      errorMessage: '',
      labels: {
        url: '',
        email: '',
        title: '',
        description: '',
        send: '',
        sending: ''
      },
      submitting: false,
    }
  },
  created() {
    this.$store.dispatch('configuration/fetchConfiguration');
    this.fetchLinksList(this.currentPage);
    this.setTitle();
  },
  mounted() {
  },
  computed: {
    ...mapState('configuration', {
      configLoading: 'loading', // loading per configuration
      configuration: 'configuration'
    }),
    ...mapState('links', {
      linksLoading: 'loading', // loading per links
      links: 'linkexchanges',
      totalCount: 'totalCount'
    })
  },
  watch: {
    '$route.query.page': function(newPage) {
      // Aggiorna currentPage e fetch
      this.currentPage = parseInt(newPage) || 0;
      this.fetchLinksList(this.currentPage);
    }
  },
  methods: {
    async setTitle() {
      this.title = await this.translate('Links'); // Attendi la traduzione e aggiorna il titolo
      this.addlink_title = await this.translate('Add your link'); // Attendi la traduzione e aggiorna il titolo
      this.labels.url = await this.translate('Insert your URL');
      this.labels.email = await this.translate('Your Email');
      this.labels.title = await this.translate('Your Site Name');
      this.labels.description = await this.translate('Your Site Description');
      this.labels.send = await this.translate('Send');
      this.labels.sending = await this.translate('Sending...');
    },
    fetchLinksList(page = 0) {
        this.$store.dispatch('links/flushLinks');
        this.$store.dispatch('links/fetchAllLinks', {filters: {
            criteria: [
              {key: 'website_id', value: ""+this.$store.getters['appState/website_id']}, 
              {key: 'locale', value: ""+this.$store.getters['appState/locale']},
              {key: 'active', value: "1"}
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
    },
    async submitForm() {
      try {
        this.successMessage = '';
        this.errorMessage = '';
        this.submitting = true;

        const response = await this.$store.dispatch('links/submitLink', {url: this.form.url, email: this.form.email, title: this.form.title, description: this.form.description});

        if (response.success) {
          this.successMessage = response.message;
          this.form = { url: '', email: '', title: '', description: '' };
        } else {
          this.errorMessage = response.message;
        }

      } catch (e) {
        console.error(e);
        alert('Errore durante l\'invio');
      } finally {
        this.submitting = false;
      }
    },
  },
  beforeRouteUpdate(to, from, next) {
    // Aggiorna currentPage se cambia la pagina nella query string
    this.currentPage = parseInt(to.query.page) || 0;
    this.fetchLinksList(this.currentPage);
    next();
  }
}
</script>

<style lang="sass">
</style>