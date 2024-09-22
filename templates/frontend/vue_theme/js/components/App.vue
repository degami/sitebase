<template>
  <div class="menu">
    <PageRegion v-if="currentRewrite" region="pre_menu" :rewriteId="currentRewrite.id"/>
    <Menu v-if="primary_menu && website_id" :menuName="primary_menu" :websiteId="website_id" @data-sent="handleMenuData"></Menu>
    <PageRegion v-if="currentRewrite" region="post_menu" :rewriteId="currentRewrite.id"/>
  </div>

  <div class="header">
    <PageRegion v-if="currentRewrite" region="pre_header" :rewriteId="currentRewrite.id"/>
    <PageRegion v-if="currentRewrite" region="post_header" :rewriteId="currentRewrite.id"/>
  </div>

  <div class="content">
    <PageRegion v-if="currentRewrite" region="pre_content" :rewriteId="currentRewrite.id"/>
    <router-view @data-sent="handleViewData"></router-view>
    <PageRegion v-if="currentRewrite" region="post_content" :rewriteId="currentRewrite.id"/>
  </div>

  <div class="footer">
    <PageRegion v-if="currentRewrite" region="pre_footer" :rewriteId="currentRewrite.id"/>
    <PageRegion v-if="currentRewrite" region="post_footer" :rewriteId="currentRewrite.id"/>
  </div>

<!--
<OtherComponent />
-->  
</template>

<script>
import OtherComponent from './OtherComponent.vue';
import Menu from './Menu.vue';
import PageRegion from './PageRegion.vue';
import Loader from '../utils/Loader.vue';

export default {
  components: {
    Loader,
    OtherComponent, // Registra il componente figlio
    Menu,
    PageRegion,
  },
  setup() {
  },
  data() {
    return {
      website_id: null,
      primary_menu: null,
      currentRewrite: null,
    };
  },
  async created() {
  },
  computed: {
  },
  async mounted() {
    this.primary_menu = await this.getConfigValue('app/frontend/main_menu', this.currentRewrite?.locale);
    this.website_id = await this.getWebsiteId(this.currentRewrite?.locale);
    this.rewrites = await this.$store.dispatch('rewrites/fetchRewrites', this.website_id);
  },
  methods: {
    async getConfigValue(path, locale = null) {
        return await this.$store.dispatch('configuration/getConfigurationByPath', { 
          path, 
          locale, 
          siteDomain: window.location.hostname 
        });
    },
    async getWebsiteId(locale = null) {
      return await this.$store.dispatch('configuration/getWebsiteId', { 
          locale, 
          siteDomain: window.location.hostname 
        });
    },
    async handleMenuData(data) {
      if ('Rewrite' == data.__typename) {
        this.currentRewrite = data;
      } else {
        this.currentRewrite = await this.$store.dispatch('rewrites/findRewriteById', {rewriteId: data.rewrite_id, websiteId: this.website_id});
      }
      this.$store.dispatch('apolloClient/updateLocale', this.currentRewrite?.locale);
      let menuName = await this.getConfigValue('app/frontend/main_menu', this.currentRewrite?.locale);
      if (menuName) {
        this.primary_menu = menuName;
      }
    },
    async handleViewData(data) {
      if (null == this.website_id) {
        this.website_id = await this.getWebsiteId();
      }

      if (data.rewrite_id) {
        this.handleMenuData(data);
      }
      let foundMenuItem = null;
      if (data.page_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/page/'+data.page_id, websiteId: this.website_id});
      }
      if (data.news_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/news/'+data.news_id, websiteId: this.website_id});
      }
      if (data.event_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/event/'+data.event_id, websiteId: this.website_id});
      }
      if (data.term_id) {
        foundMenuItem = await this.$store.dispatch('rewrites/findRewriteByRoute', {route: '/taxonomy/'+data.term_id, websiteId: this.website_id});
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
