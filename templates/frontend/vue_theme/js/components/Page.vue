<template>
    <div v-if="pagesLoading">
      <Loader text="Loading pages..." />
    </div>
    <div v-else>
      <h1 class="page-title">{{ pages[id]?.title }}</h1>
      <div class="page-content" v-html="pages[id]?.content"></div>

      <div v-if="pages[id]?.gallery.length" class="page-gallery" ref="galleryContainer">
        <div class="row gallery">
          <img v-for="galleryItem in pages[id]?.gallery" class="img-fluid img-thumbnail" :src="galleryItem.getThumbUrl_300x200" :data-gallery-id="'gallery-'+id" :data-gallery-src="galleryItem.getImageUrl" :data-gallery-desc="galleryItem.filename" />
        </div>
      </div>
    </div>
</template>
  
<script>
  import { mapState } from 'vuex'; 
  import Gallery from 'gallery';
  import Loader from '../utils/Loader.vue';

  export default {
    components: {
      Loader
    },
    props: {
      id: {
        type: Number,
        required: true
      },
      locale: {
        type: String,
        required: true
      }
    },
    data() {
    },
    created() {
      this.$store.dispatch('configuration/fetchConfiguration');
      this.updatePageContent(this.id);
    },
    computed: {
      ...mapState('configuration', {
        configLoading: 'loading', // loading per configuration
        configuration: 'configuration'
      }),
      ...mapState('pages', {
        pagesLoading: 'loading', // loading per pages
        pages: 'pages'
      })
    },
    mounted() {
      this.$emit('data-sent', {page_id: this.id});
    },
    watch: {
      '$route.params.id': function (newId) {
        this.updatePageContent(newId);
      }
    },
    methods: {
      updatePageContent(id) {
        this.$store.dispatch('pages/fetchPage', id).then(() => {
          this.$nextTick(() => {
            this.initializeGallery();
          });
        });
      },
      initializeGallery() 
      {
        this.$nextTick(() => {
          const galleryElement = this.$refs.galleryContainer;
          if (galleryElement) {
            Gallery(galleryElement, {
              // Opzioni per la tua gallery
            });
          }
        });
      }
    }
  };
  </script>