<template>
  <!-- Utilizza il componente risolto dinamicamente -->
  <component :is="resolvedComponent" :key="componentKey" v-bind="componentProps" />
</template>

<script>
import { getComponentMap } from '../router';

export default {
  props: {
    locale: {
        type: String,
        required: true
    },
    customUrl: {
        type: String,
        required: true
    },
  },
  data() {
    return {
      resolvedComponent: null,
      componentProps: {},
      componentKey: '', // Nuova chiave dinamica per forzare il remount del componente
    };
  },
  async beforeRouteEnter(to, from, next) {
    next(async (vm) => {
      await vm.resolveRoute(to);
    });
  },
  async beforeRouteUpdate(to, from, next) {
    await this.resolveRoute(to);
    next();
  },
  methods: {
    async getWebsite() {
      return await this.$store.dispatch('website/getWebsite', { 
          siteDomain: window.location.hostname 
        }, { root: true });
    },
    async resolveRoute(route) {
      const store = this.$store;


      let websiteId = store.getters['appState/website_id'];
      if (null === websiteId) {
        const website = await this.getWebsite();
        websiteId = website.id;
      }

      // Recupera la riscrittura dalla store Vuex
      const rewrite = await store.dispatch('rewrites/findRewriteByUrl', {
        url: route.path,
        websiteId:  websiteId,
      });

      if (rewrite) {
        // Aggiorna lo stato dell'app
        store.dispatch('appState/updateLocale', rewrite.locale);
        store.dispatch('appState/updateWebsiteId', rewrite.website.id);
        store.dispatch('apolloClient/updateLocale', rewrite.locale);

        // Mappa che collega le rotte interne ai componenti
        const componentMap = getComponentMap();

        // Estrai il tipo di componente (ad esempio, 'event', 'news', ecc.) dalla rotta riscritta
        let componentType = rewrite.route.split('/')[1];
        if (rewrite.route.split('/').length === 2 && componentMap[componentType + 'list']) {
          componentType += 'list';
        }

        const componentLoader = componentMap[componentType];

        if (componentLoader) {
          // Carica dinamicamente il componente risolto
          const resolvedComponent = await componentLoader();
          this.resolvedComponent = resolvedComponent.default;

          const idValue = rewrite.route.split('/').pop();

          let dataToEmit = null;
          switch (componentType) {
            case 'event':
              dataToEmit = {event_id: idValue};
              break;
            case 'news':
              dataToEmit = {news_id: idValue};
              break;
            case 'page':
              dataToEmit = {page_id: idValue};
              break;
            case 'taxonomy':
              dataToEmit = {term_id: idValue};
              break;
          }

          if (dataToEmit) {
            this.$emit('data-sent', dataToEmit);
          }

          this.componentProps = {
            id: idValue,  // Ottieni l'ID dalla route
            locale: rewrite.locale,
          };

          // Imposta una chiave dinamica basata sulla rotta per forzare il remount del componente
          this.componentKey = `${componentType}-${this.componentProps.id}`;
        } else {
          console.error(`Componente per "${componentType}" non trovato`);
        }
      } else {
        // Mappa che collega le rotte interne ai componenti
        const componentMap = getComponentMap();

        let locale = route.path.split('/')[1];
        let componentType = route.path.split('/')[2];

        console.log("componentType: " + componentType);


        if (componentMap[componentType]) {
          const componentLoader = componentMap[componentType];
          if (componentLoader) {
            // Carica dinamicamente il componente risolto
            const resolvedComponent = await componentLoader();
            this.resolvedComponent = resolvedComponent.default;

            this.componentProps = {
              "locale": locale,
            };

            store.dispatch('appState/updateLocale', locale);

            const defaultWebsiteId = await store.dispatch('configuration/getWebsiteId', { 
              siteDomain: window.location.hostname 
            });

            store.dispatch('appState/updateWebsiteId', defaultWebsiteId);
            store.dispatch('apolloClient/updateLocale', locale);

            this.componentKey = `${componentType}-${this.componentProps.locale}`;
          }
        }
      }
    },
  },
};
</script>
