<template>
<svg version="1.1" id="L4" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
  viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
  <circle :fill="color" stroke="none" cx="6" cy="50" r="6">
    <animate
      attributeName="opacity"
      dur="1s"
      values="0;1;0"
      repeatCount="indefinite"
      begin="0.1"/>    
  </circle>
  <circle :fill="color" stroke="none" cx="26" cy="50" r="6">
    <animate
      attributeName="opacity"
      dur="1s"
      values="0;1;0"
      repeatCount="indefinite" 
      begin="0.2"/>       
  </circle>
  <circle :fill="color" stroke="none" cx="46" cy="50" r="6">
    <animate
      attributeName="opacity"
      dur="1s"
      values="0;1;0"
      repeatCount="indefinite" 
      begin="0.3"/>     
  </circle>
</svg> {{ loadingText }}
</template>

<script>
export default {
    props: {
        text: {
            type: String,
            required: false
        },
        color: {
            type: String,
            required: false,
            default: '#666',
        }
    },
    data() {
      return {
        loadingText: '',
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
        return this.$store.dispatch('appState/translate', {text});
      }
    }
}
</script>

<style lang="css" scoped>

svg{
    width: 30px;
    height: 30px;
    margin: 5px;
    display:inline-block;
}
</style>