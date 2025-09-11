<template>
  <div class="fullscreen-loader" :style="{ background: background }">
    <div class="loader-content text-center">
      <img v-if="logoSrc" :src="logoSrc" alt="Logo" class="loader-logo" />
      <svg class="spinner" viewBox="0 0 50 50">
        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"/>
      </svg>
      <p class="loading-text">{{ loadingText }}</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    text: {
      type: String,
      default: 'Loading your experience...'
    },
    color: {
      type: String,
      default: '#ffffff'
    },
    background: {
      type: String,
      default: 'rgba(0, 0, 0, 0.6)'
    },
    logoSrc: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      loadingText: ''
    }
  },
  created() {
    this.setLoadingText();
  },
  methods: {
    async setLoadingText() {
      if (this.text) {
        this.loadingText = await this.translate(this.text);
      }
    },
    async translate(text) {
      if (this.$store) {
        return this.$store.dispatch('appState/translate', { text });
      }
      return text;
    }
  }
}
</script>

<style scoped>
.fullscreen-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loader-content {
  text-align: center;
  color: var(--color, #fff);
}

.loader-logo {
  max-width: 150px;
  display: block;
  margin:auto;
  margin-bottom: 20px;
  align-self: center;
}

.spinner {
  animation: rotate 2s linear infinite;
  width: 80px;
  height: 80px;
  margin-bottom: 20px;
}

.path {
  stroke: var(--color, #fff);
  stroke-linecap: round;
  animation: dash 1.5s ease-in-out infinite;
}

.loading-text {
  font-size: 1.5rem;
  font-weight: bold;
  color: var(--color, #fff);
}

/* Animazioni */
@keyframes rotate {
  100% { transform: rotate(360deg); }
}

@keyframes dash {
  0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
  50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
  100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
}
</style>
