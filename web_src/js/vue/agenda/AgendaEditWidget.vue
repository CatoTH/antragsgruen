<template>
  <section class="agendaEditWidget stdSortingWidget">
    <div class="settings">
      <label>
        <input type="checkbox" v-model="showTime" :disabled="anyItemHasTime"
               class="showTimeSelector"> <span v-t="['admin', 'agenda_show_times']"></span>
      </label>
    </div>
    <agenda-sorter v-model="list" :motionTypes="motionTypes" :root="true" :showTime="showTime"></agenda-sorter>

    <div class="saveRow" :class="{saving: saving, saved: saved, savable: !saving && !saved}">
      <div class="savable">
        <button type="button" @click="saveAgenda()" class="btn btn-primary btnSave" v-t="['admin', 'agenda_sort_save']"></button>
      </div>
      <div class="saving" v-t="['base', 'saving', false, {}, '...']"></div>
      <div class="saved">
        <span class="glyphicon glyphicon-ok"
              aria-hidden="true"></span> <template v-t="['base', 'saved']"></template>
      </div>
    </div>
  </section>
</template>

<script type="module">
export default {
  props: {
    modelValue: { type: Object },
    motionTypes: { type: Array }
  },
  computed: {
    list: {
      get: function () {
        return this.modelValue.items;
      },
      set: function (value) {
        this.$emit('update:modelValue', {"items": value});
      }
    },
    anyItemHasTime: function () {
      const checkItemRec = function(items) {
        for (let item in items) {
          if (items[item].time) {
            return true;
          }
          if (checkItemRec(items[item].children)) {
            return true;
          }
        }
        return false;
      };
      return checkItemRec(this.modelValue.items);
    }
  },
  data() {
    return {
      showTime: false,
      saving: false,
      saved: false,
    }
  },
  methods: {
    saveAgenda: function() {
      this.saving = true;
      this.$emit('save-agenda');
    },
    onSaved: function() {
      this.saving = false;
      this.saved = true;
      setTimeout(() => {
        this.saved = false;
      }, 2000);
    },
    getAgendaTest: function() {
      return this.modelValue;
    },
    setAgendaTest: function(value) {
      this.$emit('update:modelValue', value);
    }
  },
  created: function () {
    this.showTime = this.anyItemHasTime;
  }
}
</script>
