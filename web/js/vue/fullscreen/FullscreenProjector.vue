<template>
  <div class="fullscreenMainHolder" :class="{'splitscreen': splitscreen}">
    <header>
      <button class="btn btn-link splitscreenBtn" type="button" @click="toggleSplitscreen()"
              v-t:title="['pages', 'fullscr_split']" v-t:aria-label="['pages', 'fullscr_split']">
        <span class="glyphicon glyphicon-pause" aria-hidden="true"></span>
      </button>
      <button class="btn btn-link closeBtn" type="button" @click="close()" v-t:title="['base', 'aria_close']" v-t:aria-label="['base', 'aria_close']">
        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
      </button>
    </header>

    <fullscreen-panel class="primary" :class="{'splitscreen': splitscreen}" :initdata="initdata" @changed="onFirstContentChanged($event)"></fullscreen-panel>

    <fullscreen-panel class="secondary" :class="{'splitscreen': splitscreen}" :initdata="initdata" v-if="splitscreen"></fullscreen-panel>
  </div>
</template>

<script>
export default {
  props: ['initdata'],
  data() {
    return {
      splitscreen: false,
      url_html: null
    }
  },
  methods: {
    close: function () {
      this.$emit('close', this.url_html);
    },
    onFirstContentChanged: function ($event) {
      this.url_html = $event.url_html;
      this.$emit('changed', $event);
    },
    toggleSplitscreen: function () {
      this.splitscreen = !this.splitscreen;
    }
  }
};
</script>
