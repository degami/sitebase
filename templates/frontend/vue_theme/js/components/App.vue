<template>
  <FullscreenLoader v-if="prefetchLoading" text="Loading your experience..." logo-src="/sitebase_logo.png"  />
  <div v-else>
  <PageRegion region="after_body_open" :rewriteId="currentRewrite?.id" :routePath="currentPath" />

  <div class="menu">
    <PageRegion region="pre_menu" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
    <Menu v-if="primary_menu && website_id" :menuName="primary_menu" :websiteId="website_id" @data-sent="handleMenuData"></Menu>
    <PageRegion region="post_menu" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
  </div>

  <div class="header">
    <PageRegion region="pre_header" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
    <PageRegion region="post_header" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
  </div>

  <div class="content">
    <PageRegion region="pre_content" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
    <router-view @data-sent="handleViewData"></router-view>
    <PageRegion region="post_content" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
  </div>

  <div class="footer">
    <PageRegion region="pre_footer" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
    <PageRegion region="post_footer" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
  </div>

  <PageRegion region="before_body_close" :rewriteId="currentRewrite?.id" :routePath="currentPath" />
  </div>
</template>

<script>
import { mapState } from 'vuex';
import Menu from './Menu.vue';
import PageRegion from './PageRegion.vue';
import Loader from '../utils/Loader.vue';
import FullscreenLoader from '../utils/FullscreenLoader.vue';

export default {
  components: {
    FullscreenLoader,
    Loader,
    Menu,
    PageRegion,
  },
  setup() {
  },
  data() {
    return {
      primary_menu: null,
      currentRewrite: null,
      currentPath: null,
    };
  },
  async created() {
  },
  computed: {
    locale() {
      return this.$store.getters['appState/locale'];
    },
    website_id() {
      return this.$store.getters['appState/website_id'];
    },
    primary_menu() {
      return this.$store.getters['appState/primary_menu'];
    },
    ...mapState('contentPrefetch', {
      prefetchLoading: 'loading', // loading per prefetch
    })
  },
  async mounted() {
    const website = await this.getWebsite();
    let locale = this.$route.params.locale || this.currentRewrite?.locale || this.$store.getters['appState/locale'] || website.default_locale;
    this.$store.dispatch('appState/updateLocale', locale, { root: true });

    this.$store.dispatch("contentPrefetch/prefetchAll");
//    const website = await this.getWebsite();
//    let locale = this.$route.params.locale || this.currentRewrite?.locale || this.$store.getters['appState/locale'] || website.default_locale;
//    this.$store.dispatch('appState/updateLocale', locale, { root: true });
//    this.$store.dispatch('appState/updateWebsiteId', website.id, { root: true });
//    this.$store.dispatch('appState/fetchTranslations', { root: true });
//    this.rewrites = await this.$store.dispatch('rewrites/fetchRewrites', {websiteId: this.$store.getters['appState/website_id']}, { root: true });
  },
  methods: {
    async getConfigValue(path, locale = null) {
        return await this.$store.dispatch('configuration/getConfigurationByPath', { 
          path, 
          locale, 
          siteDomain: window.location.hostname 
        }, { root: true });
    },
    async getWebsite() {
      return await this.$store.dispatch('website/getWebsite', { 
          siteDomain: window.location.hostname 
        }, { root: true });
    },
    async handleRoutePath(routePath) {
      this.currentPath = routePath;
      this.$store.dispatch('pageregions/fetchPageregions', {param: routePath}, { root: true });
    },
    async handleMenuData(data) {
      if ('Rewrite' == data.__typename) {
        this.currentRewrite = data;
      } else {
        this.currentRewrite = await this.$store.dispatch('rewrites/findRewriteById', {rewriteId: data.rewrite_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }
      const rewriteId = this.currentRewrite ? this.currentRewrite.id : this.$route.path;
      this.$store.dispatch('pageregions/fetchPageregions', {param: rewriteId}, { root: true });
      let newLocale = this.currentRewrite?.locale || this.$store.getters['appState/locale'];
      if (this.$store.getters['locale'] != newLocale) {
        this.$store.dispatch('appState/updateLocale', newLocale, { root: true });
        this.$store.dispatch('appState/fetchTranslations', { root: true });
      }
      let menuName = await this.getConfigValue('app/frontend/main_menu', newLocale);
      if (menuName) {
        this.primary_menu = menuName;
      }
    },
    async handleViewData(data) {
      if (null == this.$store.getters['appState/website_id']) {
        const website = await this.getWebsite();
        this.$store.dispatch('appState/updateWebsiteId', website.id, { root: true });
      }

      if (data.rewrite_id) {
        this.handleMenuData(data);
      }
      let foundMenuItem = null;
      if (data.page_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/page/'+data.page_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }
      if (data.news_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/news/'+data.news_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }
      if (data.event_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/event/'+data.event_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }
      if (data.term_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/taxonomy/'+data.term_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }

      if (null != foundMenuItem) {
        this.handleMenuData(foundMenuItem);
      } else {
        this.currentRewrite = null;
      }

      if (data.route_path) {
        this.handleRoutePath(data.route_path);
      }
    }
  }
};
</script>

<style lang="scss">
@import "../../../../../scss/site.scss";
</style>
