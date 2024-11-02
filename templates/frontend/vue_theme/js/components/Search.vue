<template>
    <div v-if="searchResultsLoading">
      <Loader text="Loading search results ..." />
    </div>
    <div v-else>
      <h1 class="page-title">{{ title }}</h1>
      <div v-if="searchResult" class="page-content">
        <ul>
            <li v-for="searchResultItem in searchResult">
                <div class="title">
                  <router-link :to="searchResultItem.frontend_url">{{ searchResultItem.title }}</router-link>
                 </div>
                <div class="excerpt">
                  {{ searchResultItem.excerpt }}
                  <router-link :to="searchResultItem.frontend_url">{{ showmore }}</router-link>
                </div>
            </li>
        </ul>
        <Paginator :current_page="currentPage" :total="totalCount" :page_size="pageSize" :extra_query_params="{q: queryString()}" />
    </div>
    <h3 v-else>{{ no_elements_found }}</h3>
  </div>
</template>
  
<script>
  import { mapState } from 'vuex'; 
  import Loader from '../utils/Loader.vue';
  import Paginator from '../utils/Paginator.vue';

  export default {
    components: {
      Loader,
      Paginator
    },
    props: {
    },
    data() {
      return {
        title: '',
        showmore: '',
        no_elements_found: '',
        pageSize: 10,
        currentPage: this.$route.query.page || 0,
        currentQuery: this.queryString(),
      };
    },
    created() {
      this.$store.dispatch('configuration/fetchConfiguration');
      this.setTitle();
      this.setShowmore();
      this.setNoElementsFound();
      this.updateSearchResult(this.queryString());
    },
    computed: {
      ...mapState('configuration', {
        configLoading: 'loading', // loading per configuration
        configuration: 'configuration'
      }),
      ...mapState('search', {
        searchResultsLoading: 'loading', // loading per configuration
        searchResult: 'results',
        totalCount: 'totalCount'
      }),
    },
    mounted() {
    },
    watch: {
      '$route.query.q': function (inputQuery) {
        this.updateSearchResult(inputQuery, this.currentPage);
      },
      '$route.query.page': function (newPage) {
        this.updateSearchResult(this.currentQuery, newPage);
      }
    },
    methods: {
      async setTitle() {
        this.title = await this.translate('Search');
      },
      async setShowmore() {
        this.showmore = await this.translate('show more');
      },
      async setNoElementsFound() {
        this.no_elements_found = await this.translate('No elements found !');
      },
      async translate(text) {
        return this.$store.dispatch('appState/translate', {text});
      },      
      updateSearchResult(inputQuery, page = 0) {
        this.currentQuery = inputQuery;
        this.currentPage = page;
        this.$store.dispatch('search/doSearch', {
          searchString: inputQuery, 
          locale: this.$store.getters['appState/locale'], 
          page: parseInt(this.currentPage)
        });
      },
      queryString() {
        return ""+this.$route.query.q;
      }
    }
  };
  </script>