<template>
    <div v-if="pageregionsLoading">
      <Loader :text="loadingText" />
    </div>
    <div v-else class="pageregion-content" :class="region" ref="content" v-html="pageRegion"></div>
</template>
  
<script>
  import $ from 'jquery';
  window.$ = $;
  window.jQuery = $;
  import 'jquery.cycle2';
  import 'jquery.cookie'; 
  import 'jquery-lazy';
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
        _onDelegatedClick: null, // riferimento al listener per rimozione
        _onDelegatedSubmit: null, // riferimento al listener per rimozione
      };
    },
    async created() {
      this.setLoadingText();
      this.loadPageRegion();
    },
    mounted() {
      // ATTENZIONE: agganciamo il listener al root del componente (this.$el)
      this._onDelegatedClick = async (e) => {
        // ignora se l'evento è già stato gestito
        if (e.defaultPrevented) return;

        // supporto per middle/modified clicks: lascia il comportamento di default
        if (e.button && e.button !== 0) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;

        let href = a.getAttribute('href');
        if (!href) return;

        // rispetta target (es. _blank)
        const target = a.getAttribute('target');
        if (target && target !== '_self') return;

        // normalizza il path (gestisce anche link relativi)
        let normalized;

        try {
          const currentUrlObj = new URL(window.location.href, window.location.origin);
          const linkUrlObj = new URL(href);

          if (currentUrlObj.origin == linkUrlObj.origin) {
            href = linkUrlObj.pathname + linkUrlObj.search;
          }
        } catch (err) {
          // fallback semplice
          normalized = href.split('?')[0];
        }

        // escludi link esterni, mailto, tel, ancore
        if (href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('#')) {
          return;
        }

        try {
          const urlObj = new URL(href, window.location.origin);
          normalized = urlObj.pathname + urlObj.search;
        } catch (err) {
          // fallback semplice
          normalized = href.split('?')[0];
        }

        // vogliamo gestire solo path che iniziano con '/'
        if (!normalized.startsWith('/')) return;

        // ottieni websiteId (fallback a configuration se non presente)
        let websiteId = this.$store.getters['appState/website_id'];
        if (!websiteId) {
          // chiama configuration/getWebsiteId; qui usiamo direttamente this.$store
          try {
            websiteId = await this.$store.dispatch('configuration/getWebsiteId', { siteDomain: window.location.hostname });
          } catch (err) {
            // ignore — se non otteniamo websiteId procediamo a non intercettare
          }
        }

        // usa lo store per verificare la rewrite
        try {
          if (normalized == '/') {
            e.preventDefault();
            this.$router.push({ path: '/' }).catch(() => {});
            return;
          }

          const rewrite = await this.$store.dispatch('rewrites/findRewriteByUrl', { url: normalized, websiteId });
          if (rewrite) {
            e.preventDefault();

            // opzionale: aggiorna appState / apollo locale se vuoi
            if (rewrite.locale) {
              this.$store.dispatch('appState/updateLocale', rewrite.locale, false, { root: true });
            }
            if (rewrite.website && rewrite.website.id) {
              this.$store.dispatch('appState/updateWebsiteId', rewrite.website.id, { root: true });
            }

            // naviga con router (path normalizzato)
            this.$router.push({ path: normalized }).catch(() => {});
            return;
          }
        } catch (err) {
          // se la ricerca della rewrite fallisce, non bloccare il link
          // (opzionalmente log)
          // console.error('rewrite check failed', err);
        }
      };

      this._onDelegatedSubmit = async (e) => {
        const form = e.target.closest('form');
        if (!form) return;

        let action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'GET').toUpperCase();

        // Considera solo form GET e interne
        if (method !== 'GET') return;

        try {
          const currentUrlObj = new URL(window.location.href, window.location.origin);
          const actionUrlObj = new URL(action);

          if (currentUrlObj.origin == actionUrlObj.origin) {
            action = actionUrlObj.pathname + actionUrlObj.search;
          }
        } catch (err) {
          // fallback semplice
          action = action.split('?')[0];
        }

        if (!action.startsWith(window.location.origin) && !action.startsWith('/')) return;

        e.preventDefault(); // <-- blocca submit nativo

        const formData = new FormData(form);
        const query = {};
        for (const [key, value] of formData.entries()) {
          query[key] = value;
        }

        // Normalizza l'URL target
        let normalized;
        try {
          const urlObj = new URL(action, window.location.origin);
          normalized = urlObj.pathname;
        } catch (err) {
          normalized = action;
        }

        // Usa Vue Router per navigare
        this.$router.push({ path: normalized, query }).catch(() => {});
      };
    },

    beforeUnmount() {
      if (this._onDelegatedClick && this.$refs.content) {
        this.$refs.content.removeEventListener("click", this._onDelegatedClick);
        this._onDelegatedClick = null;
      }
      if (this._onDelegatedSubmit && this.$refs.content) {
        this.$refs.content.removeEventListener("click", this._onDelegatedSubmit);
        this._onDelegatedSubmit = null;
      }
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

          this.$nextTick(() => {
              if (this.$refs.content) {
                this.$refs.content.addEventListener("click", this._onDelegatedClick, { passive: false });
              }
          });

          if (this.pageRegion && this.pageRegion.includes('cycle-slideshow')) {
            this.$nextTick(() => {
              if (typeof $ !== 'undefined') {
                $('img[data-src]').Lazy();
              } else {
                console.error('jQuery non è definito');
              }
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

          if (this.pageRegion && this.pageRegion.includes('form')) {
            this.$nextTick(() => {
              this.$refs.content.addEventListener("submit", this._onDelegatedSubmit, { passive: false });
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
      },
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