<template>
    <div v-if="pageregionsLoading">
      <Loader text="Loading page region..." />
    </div>
    <div v-else v-html="pageRegion"></div>
</template>
  
<script>
  import 'jquery';
  import 'jquery.cycle2';
  import Loader from '../utils/Loader.vue';

  export default {
    components: {
      Loader
    },
    props: {
        region: {
            type: String,
            required: true
        },
        rewriteId: {
            type: Number,
            required: true
        },
    },
    data() {
      return {
        pageRegion: null,
        pageregionsLoading: false,
      };
    },
    async created() {
      await this.loadPageRegion();
    },
    computed: {
    },
    watch: {
      rewriteId: 'loadPageRegion',
      region: 'loadPageRegion',
    },
    methods: {
      async loadPageRegion() {
        this.pageregionsLoading = true;

        try {
          this.pageRegion = await this.$store.dispatch('pageregions/getPageregion', {
            rewriteId: this.rewriteId,
            region: this.region
          });

          if (this.pageRegion && this.pageRegion.includes('cycle-slideshow')) {
            this.$nextTick(() => {
              $('.cycle-slideshow').cycle();
            });
          }
        } catch (error) {
          console.error('Errore durante il caricamento della pagina:', error);
        } finally {
          this.pageregionsLoading = false;
        }
      }
    }
  };
  </script>