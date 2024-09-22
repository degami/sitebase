<template>
  <p v-if="error">Something went wrong...</p>
  <p v-if="menuLoading">
    <Loader text="Loading Menu..." />
  </p>

  <nav v-else class="navbar navbar-expand-lg navbar-light">
    <router-link v-if="logoOnMenu == '1'" to="/" class="navbar-brand">
      <img :src="assetsDomain + '/sitebase_logo.png'" />
    </router-link>
    <button class="navbar-toggler" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigatopm">
      <soan class="navbar-toggler-icon"></soan>
    </button>
    <div id="navbarSupportedContent" class="collapse navbar-collapse">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item" v-for="treeItem in menuTree" :key="treeItem.menu_id">
        <a v-if="treeItem.children.length" :id="'navbarDropdown-' + treeItem.menu_id" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" :href="treeItem.href">{{ treeItem.title }}</a>
        <router-link v-else  @click.native="sendData" :data-rewrite_id="treeItem.rewrite_id" :to="treeItem.href" class="nav-link">{{ treeItem.title }}</router-link>
        <div v-if="treeItem.children.length" class="dropdown-menu" :aria-labelledby="'navbarDropdown-' + treeItem.menu_id">
          <router-link @click.native="sendData" :data-rewrite_id="treeItem.rewrite_id" :to="treeItem.href" class="nav-link">{{ treeItem.title }}</router-link>
          <router-link v-for="childLink in treeItem.children" @click.native="sendData" :data-rewrite_id="treeItem.rewrite_id" :to="childLink.href" class="nav-link">{{ childLink.title }}</router-link>
        </div>
      </li>
    </ul>
  </div>
  </nav>
</template>
<script>
import { mapState } from 'vuex';
import $ from 'jquery';
import Loader from '../utils/Loader.vue';

export default {
  components: {
    Loader,
  },
  props: {
    menuName: {
      type: String,
      required: true
    },
    websiteId: {
      type: Number,
      required: true
    },
    maxLevels: {
      type: Number,
      default: 3
    },
  },
  computed: {
    ...mapState('configuration', {
        configLoading: 'loading', // loading per configuration
        configuration: 'configuration'
      }),
      ...mapState('menuTree', {
        menuLoading: 'loading', // loading per menuTree
        menuTree: 'menuTree'
      })
  },
  async created() {    
  },
  async mounted() {
    this.logoOnMenu = await this.getConfigValue('app/frontend/menu_with_logo');
    this.assetsDomain = await this.getConfigValue('app/frontend/assets_domain');

    this.updateMenuData();
  },
  watch: {
    'menuName': function () {
      this.updateMenuData();
    }
  },
  methods: {
    async getConfigValue(path, locale = null) {
        return await this.$store.dispatch('configuration/getConfigurationByPath', { 
          path, 
          locale, 
          websiteId: parseInt(this.websiteId),
          //siteDomain: window.location.hostname 
        });
    },
    sendData(event) {
      const clickedLink = event.target;
      const data = {
        rewrite_id: $(clickedLink).data('rewrite_id'),
        website_id: this.websiteId,
      }
      this.$emit('data-sent', data);
    },
    updateMenuData() {
      this.$store.dispatch('menuTree/fetchMenuTree', {
        "menuName": this.menuName, 
        "websiteId": parseInt(this.websiteId), 
        "maxLevels": parseInt(this.maxLevels)
      });

      $(function () {
        $('[data-toggle="dropdown"]').dropdown();
      });
    }
  }
};
</script>
<style lang="scss">

</style>