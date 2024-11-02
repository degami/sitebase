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
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navbarSupportedContent" class="collapse navbar-collapse">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item" v-for="treeItem in menuTree" :key="treeItem.menu_id">
        <a v-if="treeItem.children.length" :id="'navbarDropdown-' + treeItem.menu_id" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" :href="treeItem.href">{{ treeItem.title }}</a>

        <component v-else  :is="isRouteValid(treeItem.internal) ? 'router-link' : 'a'" 
          @click.native="sendData" 
          :data-rewrite_id="isRouteValid(treeItem.internal) ? treeItem.rewrite_id : null" 
          :to="isRouteValid(treeItem.internal) ? treeItem.href : null"
          :href="!isRouteValid(treeItem.internal) ? treeItem.href : null"
          class="nav-link">
          {{ treeItem.title }}
        </component>

        <div v-if="treeItem.children.length" class="dropdown-menu" :aria-labelledby="'navbarDropdown-' + treeItem.menu_id">
          <component :is="isRouteValid(treeItem.internal) ? 'router-link' : 'a'" 
            @click.native="sendData" 
            :data-rewrite_id="isRouteValid(treeItem.internal) ? treeItem.rewrite_id : null" 
            :to="isRouteValid(treeItem.internal) ? treeItem.href : null"
            :href="!isRouteValid(treeItem.internal) ? treeItem.href : null"
            class="nav-link">
            {{ treeItem.title }}
          </component>
          <component v-for="childLink in treeItem.children" :is="isRouteValid(treeItem.internal) ? 'router-link' : 'a'" 
            @click.native="sendData" 
            :data-rewrite_id="childLink.rewrite_id" 
            :to="isRouteValid(treeItem.internal) ? childLink.href : null"
            :href="!isRouteValid(childLink.internal) ? childLink.href : null"
            class="nav-link">
            {{ childLink.title }}
          </component>
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
import { getComponentMap } from '../router';

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
      if (data.rewrite_id) {
        this.$emit('data-sent', data);
      }
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
    },
    // Metodo per verificare se una rotta esiste nel router
    isRouteValid(route) {
      const componentMap = getComponentMap();
      let componentType = route.split('/')[1];
      if (route.split('/').length === 2 && componentMap[componentType + 'list']) {
        componentType += 'list';
      }

      // Verifica se esiste un componente corrispondente per la rotta
      return !!componentMap[componentType];
    }
  }
};
</script>
<style lang="scss" scoped>
  .nav-link {
    cursor: pointer;
  }
</style>