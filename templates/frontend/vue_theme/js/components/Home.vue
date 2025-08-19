<template>
    <div v-if="pagesLoading">
      <Loader text="Loading pages..." />
    </div>
    <div v-else>
      <h1 class="page-title">{{ siteName }}</h1>
      <div class="page-content" v-html="pages[id]?.content"></div>

      <div v-if="pages[id]?.gallery?.length" class="page-gallery" ref="galleryContainer">
        <div class="row gallery">
          <img v-for="galleryItem in pages[id]?.gallery" class="img-fluid img-thumbnail" :src="galleryItem.thumb_url__300x200" :data-gallery-id="'gallery-'+id" :data-gallery-src="galleryItem.image_url" :data-gallery-desc="galleryItem.filename" />
        </div>
      </div>
    </div>
</template>

<script>
  import { mapState } from 'vuex';
  import Loader from '../utils/Loader.vue';

  export default {
    emits: ['dataSent'],
    props: {
      locale: {
        type: String,
        required: false
      }
    },
    components: {
      Loader
    },
    data() {
      return {
        id: null,
        redirectsToLocale: false,
      };
    },
    async created() {
      const website = await this.$store.dispatch('configuration/getWebsite', { 
          siteDomain: window.location.hostname,
      });

      let locale = null;
      this.redirectsToLocale = await this.getConfigValue('app/frontend/homepage_redirects_to_language');
      if (this.$route.path == '/' && parseInt(this.redirectsToLocale) == 1) {
        locale = this.$store.getters['appState/locale'];
        if (!locale) {
          locale = website.default_locale; 
        }

        this.$router.push('/'+locale+'/');
      }

      let matches = window.location.pathname.match(new RegExp("/([a-z]{2})/?"));
      if (matches != null && undefined != matches[1]) {
        locale = matches[1];
      }
      this.id = await this.getConfigValue('app/frontend/homepage', locale);

      this.$store.dispatch('pages/fetchPage', {pageId: this.id});
      this.siteName = website.site_name
    },
    computed: {
      ...mapState('pages', {
        pagesLoading: 'loading', // loading per pages
        pages: 'pages'
      })
    },
    mounted() {
    },
    watch: {
      'id': function(newId) {
        this.$emit('data-sent', {page_id: newId});
      }
    },
    methods: {
        async getConfigValue(path, locale = null) {
            return await this.$store.dispatch('configuration/getConfigurationByPath', { 
            path, 
            locale, 
            siteDomain: window.location.hostname 
            });
        },
    }
  };
</script>

<style lang="scss">
</style>