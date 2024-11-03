<template>
    <div v-if="pagesLoading">
      <Loader text="Loading pages..." />
    </div>
    <div v-else>
      <h1 class="page-title">{{ siteName }}</h1>
      <div class="page-content" v-html="pages[id]?.content"></div>

      <div v-if="pages[id]?.gallery.length" class="page-gallery" ref="galleryContainer">
        <div class="row gallery">
          <img v-for="galleryItem in pages[id]?.gallery" class="img-fluid img-thumbnail" :src="galleryItem.getThumbUrl_300x200" :data-gallery-id="'gallery-'+id" :data-gallery-src="galleryItem.getImageUrl" :data-gallery-desc="galleryItem.filename" />
        </div>
      </div>
    </div>
</template>

<script>
  import { mapState } from 'vuex';
  import Loader from '../utils/Loader.vue';

  export default {
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
      };
    },
    async created() {
      this.id = await this.getConfigValue('app/frontend/homepage');
      this.$store.dispatch('pages/fetchPage', {pageId: this.id});
      const website = await this.$store.dispatch('configuration/getWebsite', { 
            siteDomain: window.location.hostname,
        });
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