<template>
  <div v-if="eventLoading">
    <Loader text="Loading events..." />
  </div>
  <div v-else>
    <h1 class="event-title">{{ events[id]?.title }}</h1>
    <div class="event-map"><div :id="'mapElement'+id+'-map'" style="width: 100%; height: 300px" class="map-details"></div></div>
    <div class="event-location">lat: {{ events[id]?.latitude }}, lon: {{ events[id]?.longitude }}</div>
    <div class="event-date" v-html="events[id]?.date"></div>
    <div class="event-content" v-html="events[id]?.content"></div>
  </div>
</template>

<script>
import { mapState } from 'vuex'; 
import Loader from '../utils/Loader.vue';
import $ from 'jquery';

export default {
  emits: ['dataSent'],
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
    return {
      currentEvent: null,
      mapBoxKey: null,
      googleMapsKey: null,
    };
  },
  async created() {
    await this.$store.dispatch('configuration/fetchConfiguration');

    this.updateEventContent(this.id);
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
  async mounted() {
    this.$emit('data-sent', {event_id: this.id});

    this.$data.mapBoxKey = await this.$store.dispatch('configuration/getConfigurationByPath', { 
      path: 'app/mapbox/api_key', 
      locale: this.$store.getters['appState/locale'], 
      websiteId: this.$store.getters['appState/website_id'] 
    });
    this.$data.googleMapsKey = await this.$store.dispatch('configuration/getConfigurationByPath', { 
      path: 'app/googlemaps/api_key', 
      locale: this.$store.getters['appState/locale'], 
      websiteId: this.$store.getters['appState/website_id'] 
    });

    if (this.$data.mapBoxKey) {
      const script = document.createElement('script');
      script.src = "https://unpkg.com/leaflet@1.3.4/dist/leaflet.js";
      script.integrity = 'sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==';
      script.crossOrigin = "anonymous";
      script.onload = this.updateMap;
      document.head.appendChild(script);

      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/leaflet@1.3.4/dist/leaflet.css';
      link.integrity = 'sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==';
      link.crossOrigin = "anonymous";
      document.head.appendChild(link);
    } else if (this.$data.googleMapsKey) {
      const script = document.createElement('script');
      script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&amp&amp;libraries=geometry,places&amp;key=' +  this.$data.googleMapsKey;
      script.onload = this.updateMap;
      document.head.appendChild(script);
    }
  },
  watch: {
    '$route.params.id': function (newId) {
      this.updateEventContent(newId);
    }
  },
  methods: {
    async updateEventContent(id) {
      this.$data.currentEvent = await this.$store.dispatch('events/fetchEvent', {eventId: id});
      this.$emit('data-sent', {event_id: this.$data.currentEvent.id});
    },
    async updateMap() {
      let event = this.currentEvent || await this.$store.dispatch('events/fetchEvent', {eventId: this.id});
      if (this.$data.currentEvent == null) {
        this.$data.currentEvent = event;
      }

      if (this.$data.mapBoxKey) {
        var latlng = {
            lat: event.latitude,
            lng: event.longitude
        };
        var map = L.map('mapElement'+this.id+'-map').setView([latlng.lat,latlng.lng],10);
        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token='+this.$data.mapBoxKey, {
            attribution:
                'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors,'+
                '<a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>,'+
                ' Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox/streets-v12',
            accessToken: this.$data.mapBoxKey
        }).addTo(map);

        var marker = L.marker([latlng.lat, latlng.lng],{
            draggable: false
        }).addTo(map);

        $.data( $('#mapElement'+this.id+'-map')[0] , 'map_obj', map);
        $.data( $('#mapElement'+this.id+'-map')[0] , 'marker_obj', marker);
      } else if (this.$data.googleMapsKey) {
        var latlng = {
            lat: event.latitude,
            lng: event.longitude
        };

        var map = new google.maps.Map(document.getElementById('#mapElement'+this.id+'-map'), {
          center: latlng,
          mapTypeId: 'google.maps.MapTypeId.ROADMAP',
          scrollwheel: true,
          zoom: {$zoom}
        });
        var marker = new google.maps.Marker({
          map: map,
          draggable: true,
          animation: google.maps.Animation.DROP,
          position: latlng,
          title: event.title == null ? 
                    "lat: " + event.latitude + ", lng: " + event.longitude :
                    event.title
        });
        $.data( $('#mapElement'+this.id+'-map')[0] , 'map_obj', map);
        $.data( $('#mapElement'+this.id+'-map')[0] , 'marker_obj', marker);
      }
    }
  }
};
</script>

<style lang="scss">
</style>