<template>
  <main v-if="!isTwoColumnLayout" class="motionTextHolder" :class="{'isAmendment': isAmendment}">
    <div v-if="imotion.pagination && (imotion.pagination.prev || imotion.pagination.next)" class="paginationHolder">
      <button v-if="imotion.pagination.prev" type="button" class="btn btn-link btnPrev" @click="onPaginationChange(imotion.pagination.prev)">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <template v-t="['motion', 'prevnext_links_prev_generic']"></template>
      </button>
      <button v-if="imotion.pagination.next" type="button" class="btn btn-link btnNext" @click="onPaginationChange(imotion.pagination.next)">
        <template v-t="['motion', 'prevnext_links_next_generic']"></template>
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
      </button>
    </div>
    <table class="motionDataTable">
      <tbody>
      <tr v-if="imotion.initiators_html && imotion.initiators.length === 1">
        <th v-t="['motion', 'initiators_1', false, {}, ':']"></th>
        <td>{{ imotion.initiators_html }}</td>
      </tr>
      <tr v-if="imotion.initiators_html && imotion.initiators.length > 1">
        <th v-t="['motion', 'initiators_x', false, {}, ':']"></th>
        <td>{{ imotion.initiators_html }}</td>
      </tr>
      <tr v-if="imotion.status_title">
        <th v-t="['motion', 'status', false, {}, ':']"></th>
        <td v-html="imotion.status_title"></td>
      </tr>
      <tr v-if="imotion.proposed_procedure && imotion.proposed_procedure.status_title">
        <th v-t="['amend', 'proposed_status', false, {}, ':']"></th>
        <td v-html="imotion.proposed_procedure.status_title"></td>
      </tr>
      </tbody>
    </table>
    <section v-for="section in nonEmptySections" class="paragraph lineNumbers" :class="[section.type]">
      <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
      <div v-html="section.html"></div>
    </section>
  </main>
  <main v-if="isTwoColumnLayout" class="motionTextHolder motionTwoCols" :class="{'isAmendment': isAmendment}">
    <div class="motionMainCol">
      <section v-for="section in nonEmptyLeftSections" class="paragraph lineNumbers" :class="[section.type]">
        <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
        <div v-html="section.html"></div>
      </section>
    </div>
    <div class="motionRightCol">
      <table class="motionDataTable">
        <tbody>
        <tr v-if="imotion.initiators_html && imotion.initiators.length === 1">
          <th v-t="['motion', 'initiators_1', false, {}, ':']"></th>
          <td>{{ imotion.initiators_html }}</td>
        </tr>
        <tr v-if="imotion.initiators_html && imotion.initiators.length > 1">
          <th v-t="['motion', 'initiators_x', false, {}, ':']"></th>
          <td>{{ imotion.initiators_html }}</td>
        </tr>
        <tr v-if="imotion.status_title">
          <th v-t="['motion', 'status', false, {}, ':']"></th>
          <td v-html="imotion.status_title"></td>
        </tr>
        <tr v-if="imotion.proposed_procedure && imotion.proposed_procedure.status_title">
          <th v-t="['amend', 'proposed_status', false, {}, ':']"></th>
          <td v-html="imotion.proposed_procedure.status_title"></td>
        </tr>
        </tbody>
      </table>
      <section v-for="section in nonEmptyRightSections" class="paragraph lineNumbers" :class="[section.type]">
        <h2 v-if="showSectionTitle(section)">{{ section.title }}</h2>
        <div v-html="section.html"></div>
      </section>
    </div>
  </main>
</template>

<script>
export default {
  props: ['imotion'],
  emits: ['pagination-change'],
  computed: {
    isTwoColumnLayout: function () {
      return this.imotion.sections && this.imotion.sections.find(section => {
        return section.layout_right;
      });
    },
    nonEmptyLeftSections: function () {
      return this.imotion.sections.filter(section => {
        return !section.layout_right && section.html !== '';
      });
    },
    nonEmptyRightSections: function () {
      return this.imotion.sections.filter(section => {
        return section.layout_right && section.html !== '';
      });
    },
    nonEmptySections: function () {
      let sections = [];
      if (this.imotion.proposed_procedure && this.imotion.proposed_procedure.sections) {
        this.imotion.proposed_procedure.sections.forEach(section => {
          if (section.html !== '') sections.push(section);
        });
      }
      this.imotion.sections.forEach(section => {
        if (section.html !== '') sections.push(section);
      });
      return sections;
    },
    isAmendment: function () {
      return this.imotion && this.imotion.type === 'amendment';
    }
  },
  methods: {
    showSectionTitle: function (section) {
      return !section.layout_right && ['Image', 'PDFAttachment', 'PDFAlternative'].indexOf(section.type) === -1;
    },
    onPaginationChange: function (url) {
      this.$emit('pagination-change', url);
    }
  }
}
</script>
