<template>
  <nav class="d-flex justify-content-end" aria-label="Paginator" v-if="total_pages >= 1">
    <ul class="pagination">
      <li :class="['page-item', (parseInt(current_page) <= 0 ? 'disabled' : '')]">
        <router-link class="page-link" :to="'?page=0' + extraQueryParamsString">First</router-link>
      </li>
      <li class="page-item" v-if="parseInt(current_page) > 0">
        <router-link class="page-link" :to="'?page=' + (parseInt(current_page) - 1) + extraQueryParamsString">
          <span aria-hidden="true">«</span><span class="sr-only">Previous</span>
        </router-link>
      </li>
      <li class="page-item disabled" v-if="Math.max(0, parseInt(current_page) - parseInt(visible_links)) > 0">
        <span class="page-link">...</span>
      </li>
      <li :class="['page-item', (i == parseInt(current_page)) ? 'active' : '']" v-for="i in pages" :key="i">
        <router-link :to="'?page=' + i + extraQueryParamsString" class="page-link">{{ i + 1 }}</router-link>
      </li>
      <li class="page-item disabled" v-if="Math.min(parseInt(current_page) + parseInt(visible_links), total_pages) < total_pages">
        <span class="page-link">...</span>
      </li>
      <li class="page-item" v-if="parseInt(current_page) < parseInt(total_pages)">
        <router-link class="page-link" :to="'?page=' + (parseInt(current_page) + 1) + extraQueryParamsString">
          <span aria-hidden="true">»</span><span class="sr-only">Next</span>
        </router-link>
      </li>
      <li :class="['page-item', (parseInt(current_page) >= parseInt(total_pages) ? ' disabled' : '')]">
        <router-link class="page-link" :to="'?page=' + (total_pages) + extraQueryParamsString">Last</router-link>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
    props: {
        current_page: {
          type: Number,
          required: true
        },
        total: {
          type: Number,
          required: true
        },
        page_size: {
          type: Number,
          required: true,
          default: 50
        },
        visible_links: {
          type: Number,
          required: false,
          default: 2
        },
        extra_query_params: {
          type: Object,
          required: false,
          default: null,
        }
    },
    computed: {
      total_pages() {
        return Math.ceil(this.total / this.page_size) - 1;
      },
      pages() {
        let start = Math.max(0, parseInt(this.current_page) - parseInt(this.visible_links));
        let end = Math.min(parseInt(this.current_page) + parseInt(this.visible_links), this.total_pages);
        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
      },
      extraQueryParamsString() {
        if (!this.extra_query_params) return '';
  
        const queryParams = Object.entries(this.extra_query_params)
          .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
          .join('&');
          
        return queryParams ? '&' + queryParams : '';
      }
    }
}
</script>

<style lang="css" scoped>
</style>