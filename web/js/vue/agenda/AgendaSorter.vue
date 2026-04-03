<template>
  <draggable-plus v-model="list" class="drag-area" :animation="150" :group="disabled ? 'disabled' : 'agenda'" tag="ul" handle=".sortIndicator" @clone="onClone">
    <li v-for="(item, itemIndex) in list" :key="item.id" class="item" :class="'type_' + item.type + ' item_' + item.id">
      <agenda-edit-item-row
          v-if="item.type == 'item'"
          v-model="list[itemIndex]" :motionTypes="motionTypes"
          :codeBase="getCodeBase(itemIndex)" :showTime="showTime"
          @remove="removeItem(item)"
      />
      <agenda-sorter v-if="item.type == 'item'" v-model="list[itemIndex].children" :motionTypes="motionTypes" :disabled="disableChildList" :showTime="showTime" />

      <div v-if="item.type == 'date_separator'" class="infoRow" @remove="removeItem(item)">
        <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
        <v-datetime-picker v-model="item.date" type="date" />

        <div class="deleteHolder">
          <button class="btn btn-link btnDelete" type="button" @click="removeItem(item)" v-t:title="['con', 'agenda_del']">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </li>
  </draggable-plus>
  <div class="adderRow">
    <button type="button" class="btn btn-link adderBtn" @click="addItemRow()">
      <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
      <template v-t="['admin', 'agenda_add_item']"></template>
    </button>
    <button type="button" class="btn btn-link adderBtn" @click="addDateSeparatorRow()" v-if="root">
      <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
      <template v-t="['admin', 'agenda_add_date']"></template>
    </button>
  </div>
</template>

<script type="module">
export default {
  props: {
    modelValue: { type: Array },
    motionTypes: { type: Array },
    root: { type: Boolean },
    disabled: { type: Boolean },
    showTime: { type: Boolean }
  },
  data() {
    return {
      disableChildListExplicitly: false,
      calculatedCodes: null,
    }
  },
  watch: {
    modelValue: {
      handler(oldValue, newValue) {
        this.recalculateCodeBases(newValue);
      },
      deep: true
    }
  },
  computed: {
    list: {
      get: function () {
        return this.modelValue;
      },
      set: function (value) {
        this.$emit('update:modelValue', value);
        window.setTimeout(() => {
          this.recalculateCodeBases(value);
        }, 1);
      }
    },
    disableChildList: function () {
      return this.disabled || this.disableChildListExplicitly;
    }
  },
  methods: {
    onClone: function (evt) {
      this.disableChildListExplicitly = evt.clone.classList.contains("type_date_separator") || this.disabled;
    },
    addItemRow: function() {
      this.modelValue.push({
        id: null,
        type: 'item',
        code: null,
        title: '',
        time: null,
        children: [],
        settings: {
          in_proposed_procedures: false,
          has_speaking_list: false,
          motion_types: [],
        },
      });
      this.recalculateCodeBases(this.modelValue)
    },
    addDateSeparatorRow: function() {
      this.modelValue.push({
        id: null,
        type: 'date_separator',
        code: null,
        title: '',
        date: null,
        children: [],
        settings: {
          in_proposed_procedures: false,
          has_speaking_list: false,
          motion_types: [],
        },
      });
      this.recalculateCodeBases(this.modelValue)
    },
    removeItem: function(item) {
      const newValues = this.modelValue.filter(it => it !== item);
      this.$emit('update:modelValue', newValues);
    },
    recalculateCodeBases: function(values) {
      this.calculatedCodes = [];
      let lastValue = null;
      values.forEach((value, idx) => {
        if (value.type !== 'item') {
          this.calculatedCodes[idx] = null;
          return;
        }

        if (value.code !== null && value.code !== '') {
          this.calculatedCodes[idx] = value.code;
          lastValue = value.code;
          return;
        }

        if (lastValue === null) {
          this.calculatedCodes[idx] = "1.";
        } else if (lastValue.match(/^[a-z]\.?$/i)) {
          this.calculatedCodes[idx] = String.fromCharCode(lastValue.charCodeAt(0) + 1) + ".";
        } else {
          let strWithoutSeparator = (lastValue.substr(-1) === '.' ? lastValue.substr(0, lastValue.length - 1) : lastValue);
          let matches = strWithoutSeparator.match(/^(.*[^0-9])?([0-9]*)?$/),
              nonNumeric = (typeof (matches[1]) == 'undefined' ? '' : matches[1]),
              numeric = parseInt(matches[2] == '' ? '1' : matches[2]);
          this.calculatedCodes[idx] = nonNumeric + ++numeric + ".";
        }
        lastValue = this.calculatedCodes[idx];
      });
    },
    getCodeBase: function(itemIndex) {
      return this.calculatedCodes[itemIndex];
    }
  },
  created: function () {
    this.recalculateCodeBases(this.modelValue)
  }
}
</script>
