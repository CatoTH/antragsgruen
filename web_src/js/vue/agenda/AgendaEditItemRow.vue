<template>
  <div class="infoRow">
    <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
    <v-datetime-picker v-model="modelValue.time" type="time" v-if="showTime" />

    <input type="text" v-model="modelValue.code" :placeholder="codeBase" class="form-control codeCol"/>
    <input type="text" v-model="modelValue.title" class="form-control titleCol"/>

    <select class="stdDropdown motionTypeCol" @change="onMotionTypeChange($event)">
      <option>-</option>
      <option v-for="motionType in motionTypes" :value="motionType.id" :selected="isMotionTypeSelected(motionType)">{{ motionType.title }}</option>
    </select>

    <div class="dropdown extraSettings">
      <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <span class="glyphicon glyphicon-wrench" v-t:aria-label="['admin', 'agenda_move_aria']"></span>
        <span class="caret" aria-hidden="true"></span>
      </button>
      <input v-if="!hasProposedProcedure()" type="checkbox" v-model="modelValue.settings.in_proposed_procedures" hidden>
      <ul class="dropdown-menu dropdown-menu-right">
        <li class="checkbox inProposedProcedures" v-if="hasProposedProcedure()">
          <label>
            <input type="checkbox" v-model="modelValue.settings.in_proposed_procedures">
            <template v-t="['con', 'agenda_pp']"></template>
          </label>
        </li>
        <li class="checkbox hasSpeakingList">
          <label>
            <input type="checkbox" v-model="modelValue.settings.has_speaking_list">
            <template v-t="['con', 'agenda_speaking']"></template>
          </label>
        </li>
      </ul>
    </div>

    <div class="addLinkHolder">
      <a v-for="list in modelValue.settings.speaking_lists" :href="speakingAdminLink(list)"
         v-t:title="['admin', 'agenda_speeking_link']">
        <span class="glyphicon glyphicon-comment" aria-hidden="true"></span>
        <span class="sr-only" v-t="['admin', 'agenda_speeking_link']"></span>
      </a>

      <button class="btn btn-link btnDelete" type="button" @click="removeItem()" v-t:title="['con', 'agenda_del']">
        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        <span class="sr-only" v-t="['con', 'agenda_del']"></span>
      </button>
    </div>
  </div>
</template>

<script type="module">
let speechAdminUrlTemplate;

export default {
  setSpeechAdminUrl(url) {
    speechAdminUrlTemplate = url;
  },
  props: {
    modelValue: { type: Object },
    codeBase: { type: String },
    motionTypes: { type: Array },
    showTime: { type: Boolean }
  },
  data() {
    return {
      speechAdminUrlTemplate,
    }
  },
  methods: {
    hasProposedProcedure: function() {
      return this.motionTypes.filter(type => type.has_proposed_procedure).length > 0;
    },
    isMotionTypeSelected(motionType) {
      return this.modelValue.settings.motion_types.indexOf(motionType.id) !== -1;
    },
    onMotionTypeChange(event) {
      const hasValue = event.target.value && event.target.value !== '-';
      this.modelValue.settings.motion_types = (hasValue ? [parseInt(event.target.value)] : []);
    },
    removeItem: function() {
      this.$emit('remove');
    },
    speakingAdminLink: function(list) {
      return this.speechAdminUrlTemplate.replace(/QUEUE/, list);
    }
  },
  mounted: function () {
    this.$el.querySelectorAll(".dropdown-menu .checkbox").forEach((el) => {
      el.addEventListener("click", ev => ev.stopPropagation());
    });
  }
}
</script>
