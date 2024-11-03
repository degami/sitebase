<template>
  <h1 class="page-title" v-html="title"></h1>
  <form v-if="!currentQuery" action="" method="get">
        <div class="form-group">
        <div class="searchbar input-group">
            <input type="text" name="q" value="" class="form-control" />
            <div class="input-group-append">
                <button type="submit" :value="title" class="btn searchbtn">{{ title }}</button>
            </div>
        </div>
        </div>
  </form>
  <div v-else>
    <div v-if="searchResultsLoading">
      <Loader text="Loading search results ..." />
    </div>
    <div v-else>
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
  </div>
</template>
  
<script>
  import { mapState } from 'vuex'; 
  import Loader from '../utils/Loader.vue';
  import Paginator from '../utils/Paginator.vue';

  export default {
    emits: ['dataSent'],
    components: {
      Loader,
      Paginator
    },
    props: {
      locale: {
          type: String,
          required: false
        }
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
      this.setTranslations();
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
      this.$emit('data-sent', {});
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
      async translate(text) {
        return this.$store.dispatch('appState/translate', {text});
      },
      async setTranslations() {
        this.title = await this.translate('Search');
        this.showmore = await this.translate('show more');
        this.no_elements_found = await this.translate('No elements found !');
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
        return (undefined != this.$route.query.q) ? ""+this.$route.query.q : null;
      }
    }
  };
  </script>