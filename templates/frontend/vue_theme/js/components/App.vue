<template>
  <PageRegion region="after_body_open" :rewriteId="currentRewrite ? currentRewrite.id : null"/>

  <div class="menu">
    <PageRegion region="pre_menu" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
    <Menu v-if="primary_menu && website_id" :menuName="primary_menu" :websiteId="website_id" @data-sent="handleMenuData"></Menu>
    <PageRegion region="post_menu" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
  </div>

  <div class="header">
    <PageRegion region="pre_header" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
    <PageRegion region="post_header" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
  </div>

  <div class="content">
    <PageRegion region="pre_content" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
    <router-view @data-sent="handleViewData"></router-view>
    <PageRegion region="post_content" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
  </div>

  <div class="footer">
    <PageRegion region="pre_footer" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
    <PageRegion region="post_footer" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
  </div>

  <PageRegion region="before_body_close" :rewriteId="currentRewrite ? currentRewrite.id : null"/>
</template>

<script>
import Menu from './Menu.vue';
import PageRegion from './PageRegion.vue';
import Loader from '../utils/Loader.vue';

export default {
  components: {
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
    }
  },
  async mounted() {
    const website = await this.getWebsite();
    let locale = this.$route.params.locale || this.currentRewrite?.locale || this.$store.getters['appState/locale'] || website.default_locale;
    this.$store.dispatch('appState/updateLocale', locale, { root: true });
    this.$store.dispatch('appState/updateWebsiteId', website.id, { root: true });
    this.$store.dispatch('appState/fetchTranslations', { root: true });
    this.rewrites = await this.$store.dispatch('rewrites/fetchRewrites', {websiteId: this.$store.getters['appState/website_id']}, { root: true });
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
    async handleMenuData(data) {
      if ('Rewrite' == data.__typename) {
        this.currentRewrite = data;
      } else {
        this.currentRewrite = await this.$store.dispatch('rewrites/findRewriteById', {rewriteId: data.rewrite_id, websiteId: this.$store.getters['appState/website_id']}, { root: true });
      }
      const rewriteId = this.currentRewrite ? this.currentRewrite.id : null;
      this.$store.dispatch('pageregions/fetchPageregions', {rewriteId}, { root: true });
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
        this.$store.dispatch('appstate/updateWebsiteId', website.id, { root: true });
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
    }
  }
};
</script>

<style lang="scss">
@import "../../../../../scss/site.scss";
</style>
