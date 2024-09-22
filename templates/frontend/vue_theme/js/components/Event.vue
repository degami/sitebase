<template>
  <div v-if="eventLoading">
    <Loader text="Loading events..." />
  </div>
  <div v-else>
    <h1 class="event-title">{{ events[id]?.title }}</h1>
    <div class="event-date" v-html="events[id]?.date"></div>
    <div class="event-content" v-html="events[id]?.content"></div>
  </div>
</template>

<script>
import { mapState } from 'vuex'; 
import Loader from '../utils/Loader.vue';

export default {
  components: {
    Loader
  },
  data() {
    return {
      id: this.$route.params.id, // Imposta l'id iniziale dai parametri della rotta
    };
  },
  created() {
    this.$store.dispatch('configuration/fetchConfiguration');
    this.updateEventContent(this.$route.params.id);
  },
  computed: {
    ...mapState('configuration', {
      configLoading: 'loading', // loading per configuration
      configuration: 'configuration'
    }),
    ...mapState('events', {
      eventLoading: 'loading', // loading per news
      events: 'events'
    })
  },
  mounted() {
    this.$emit('data-sent', {event_id: this.$route.params.id});
  },
  watch: {
    '$route.params.id': function (newId) {
      this.updateEventContent(newId);
    }
  },
  methods: {
    updateEventContent(id) {
      this.$data.id = id;
      this.$store.dispatch('events/fetchEvent', this.$data.id);
    },
  }
};
</script>