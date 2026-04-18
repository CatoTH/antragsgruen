<template>
  <article class="projectorWidget">
    <header>
      <div class="imotionSelector">
        <select v-if="imotions || pages" v-model="dropdownSelection" @change="onChangeSelectedContent()" class="stdDropdown">
          <option :value="'speech'" v-if="hasOneSpeakingList" v-t="['speech', 'fullscreen_title_s']"></option>
          <!-- @TODO Multiple speaking lists -->
          <template v-for="imotion in imotions">
            <option :value="imotion.type + '-' + imotion.id">{{ imotion.title_with_prefix }}</option>
            <option v-for="amendment in getImotionAmendmentLinks(imotion)" :value="'amendment-' + amendment.id">▸ {{ amendment.prefix }}</option>
          </template>
          <option v-if="pages" disabled v-t="['pages', 'fullscr_select', false, {}, ':']"></option>
          <option v-for="page in pages" :value="'page-' + page.id">▸ {{ page.title }}</option>
        </select>
      </div>

      <h1 v-if="imotion" class="hidden">{{ imotion.title_with_prefix }}</h1>
    </header>

    <fullscreen-imotion v-if="imotion" :imotion="imotion" @paginationChange="onPaginationChange($event)"></fullscreen-imotion>

    <fullscreen-speech v-if="dropdownSelection === 'speech'" :initQueue="selectedSpeakingList" :user="null" :csrf="null" :title="'Speaking List'"></fullscreen-speech>

    <main class="contentPage" v-if="page">
      <div class="content" v-html="page.html"></div>
    </main>

  </article>
</template>

<script>
export default {
  props: ['initdata'],
  data() {
    return {
      consultationUrl: null,
      imotions: null,
      speakingLists: null,
      pages: null,
      imotion: null,
      page: null,
      dropdownSelection: null
    }
  },
  computed: {
    hasOneSpeakingList: function() {
      return this.speakingLists && this.speakingLists.length === 1;
    },
    hasMultipleSpeakingLists: function () {
      return this.speakingLists && this.speakingLists.length > 1;
    },
    selectedSpeakingList: function () {
      return this.dropdownSelection === 'speech' && this.speakingLists ? this.speakingLists[0] : null;
    }
  },
  methods: {
    loadIMotionList: function() {
      const widget = this;
      fetch(this.consultationUrl)
          .then(response => {
            if (!response.ok) throw response.statusText;
            return response.json();
          })
          .then(data => {
            widget.imotions = data.motion_links;
            widget.speakingLists = data.speaking_lists;
            widget.pages = data.page_links;
          })
          .catch(err => alert(err));
    },
    loadIMotion: function (url) {
      const widget = this;
      const urlWithParams = url + '?lineNumbers=true';
      fetch(urlWithParams)
          .then(response => {
            if (!response.ok) throw response.statusText;
            return response.json();
          })
          .then(data => {
            widget.page = null;
            widget.imotion = data;
            widget.dropdownSelection = data.type + '-' + data.id;
            widget.$emit('changed', data);
          })
          .catch(err => alert(err));
    },
    loadPage: function (url) {
      const widget = this;
      fetch(url)
          .then(response => {
            if (!response.ok) throw response.statusText;
            return response.json();
          })
          .then(data => {
            widget.page = data;
            widget.imotion = null;
            widget.dropdownSelection = 'page-' + data.id;
            widget.$emit('changed', data);
          })
          .catch(err => alert(err));
    },
    loadInitContent: function () {
      const documentType = this.initdata.init_page ? this.initdata.init_page.split("-")[0] : '';
      if (documentType === 'motion' || documentType === 'amendment') {
        this.loadIMotion(this.initdata.init_content_url);
      }
      if (documentType === 'page') {
        this.loadPage(this.initdata.init_content_url);
      }
      if (documentType === 'speech') {
        this.dropdownSelection = 'speech';
      }
    },
    onChangeSelectedContent: function () {
      const documentType = this.dropdownSelection ? this.dropdownSelection.split("-")[0] : '';
      if (documentType === 'motion' || documentType === 'amendment') {
        this.onChangeSelectedIMotion();
      }
      if (documentType === 'page') {
        this.onChangeSelectedPage();
      }
      if (documentType === 'speech') {
        this.imotion = null;
        this.page = null;
      }
    },
    onChangeSelectedIMotion: function () {
      let foundImotion = null;
      this.imotions.forEach(imotion => {
        if ((imotion.type + '-' + imotion.id) === this.dropdownSelection) {
          foundImotion = imotion;
        }
        if (imotion.amendment_links) {
          imotion.amendment_links.forEach(amendment => {
            if (('amendment-' + amendment.id) === this.dropdownSelection) {
              foundImotion = amendment;
            }
          });
        }
      });
      if (foundImotion) {
        this.loadIMotion(foundImotion.url_json);
      } else {
        this.imotion = null;
      }
    },
    onChangeSelectedPage: function () {
      let foundPage = null;
      this.pages.forEach(page => {
        if ('page-' + page.id === this.dropdownSelection) {
          foundPage = page;
        }
      });
      if (foundPage) {
        this.loadPage(foundPage.url_json);
      } else {
        this.page = null;
      }
    },
    getImotionAmendmentLinks: function (imotion) {
      return imotion.amendment_links ? imotion.amendment_links : [];
    },
    onPaginationChange: function (url) {
      this.loadIMotion(url);
    }
  },
  created() {
    this.consultationUrl = this.initdata.consultation_url;
    this.loadIMotionList();
    this.loadInitContent();
  }
}
</script>
