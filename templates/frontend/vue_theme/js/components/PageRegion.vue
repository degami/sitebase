<template>
    <div v-if="pageregionsLoading">
      <Loader :text="loadingText" />
    </div>
    <div v-else v-html="pageRegion"></div>
</template>
  
<script>
  import 'jquery';
  import 'jquery.cycle2';
  import 'jquery.cookie'; 
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
            required: false
        },
        routePath: {
            type: String,
            required: false
        },
    },
    data() {
      return {
        pageregionsLoading: false,
        loadingText: '',
        pageRegion: null,
      };
    },
    async created() {
      this.setLoadingText();
      this.loadPageRegion();
    },
    mounted() {
    },
    computed: {
    },
    watch: {
      rewriteId: 'loadPageRegion',
      region: 'loadPageRegion',
    },
    methods: {
      async setLoadingText() {
        this.loadingText = await this.translate('Loading page region %s ...', this.region);
      },
      async loadPageRegion() {
        this.pageregionsLoading = true;

        try {
          let key;
          if (this.rewriteId) {
            key = this.rewriteId;
          } else if (this.routePath) {
            key = this.routePath;
          }

          if (!key) {
            return null;
          }

          this.pageRegion = await this.$store.dispatch('pageregions/getPageregion', {
            param: key,
            region: this.region
          });

          if (this.pageRegion && this.pageRegion.includes('cycle-slideshow')) {
            this.$nextTick(() => {
              $('.cycle-slideshow').cycle();

              $('.uncachable-block').each(function(){
                  var $this = $(this);
                  if (!$this.hasClass('processed')) {
                      $.ajax('/crud/uncachableblock', {
                          method: 'POST',
                          cache: false,
                          contentType: 'application/json',
                          data: JSON.stringify($this.data('uncachable')),
                      }).done(function(data) {
                          $this.html(data.html);
                          $this.addClass('processed');
                      });
                  }
              });
            });
          }

          if (this.pageRegion && this.pageRegion.includes('cookie-notice')) {
            this.$nextTick(() => {
              $('.cookie-notice .close-btn').click(function(evt) {
                evt.preventDefault();
                $('.cookie-notice').fadeOut();
                $.cookie('cookie-accepted',1, { expires: 365, path: '/' });
                $('body').removeClass('cookie-notice-visible').addClass('cookie-notice-hidden');
              });
              if ($.cookie('cookie-accepted') != 1) {
                  $('.cookie-notice').fadeIn();
                  $('body').addClass('cookie-notice-visible');
              }
            });
          }
        } catch (error) {
          console.error('Errore durante il caricamento della pagina:', error);
        } finally {
          this.pageregionsLoading = false;
        }

        return this.pageRegion;
      },
      async translate(text, ...parameters) {
        return this.$store.dispatch('appState/translate', {text, parameters});
      }
    }
  };
</script>

<style lang="scss">
  .cookie-notice {
      position: fixed;
      left: 0;
      width:100%;
      z-index: 100000;
      padding: 10px;
  }
  
  .cookie-notice a {
      color: inherit;
  }
</style>