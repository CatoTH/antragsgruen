<template>
  <div class="v-policy-select">
    <select class="stdDropdown" @change="setPolicy($event)" :disabled="disabled">
      <option v-for="policyIterator in ALL_POLICIES" :value="policyIterator.id" :selected="policy.id === policyIterator.id">{{ policyIterator.title }}</option>
    </select>
    <v-selectize v-if="policyId === POLICY_USER_GROUPS" multiple
                 @change="setSelectedGroups($event)"
                 :disabled="disabled"
                 :options="userGroupOptions"
                 :values="userGroups"
                 :loadUrl="GROUP_LOAD_URL"
    ></v-selectize>

  </div>
</template>

<script>
let POLICY_USER_GROUPS,
    ALL_POLICIES,
    GROUP_LOAD_URL;
export default {
  setConstants(groupLoadUrl, groupPolicy, allPolicies) {
      POLICY_USER_GROUPS = groupPolicy;
      ALL_POLICIES = allPolicies;
      GROUP_LOAD_URL = groupLoadUrl;
  },
  props: ['allGroups', 'allowAnonymous', 'policy', 'disabled'],
  data() {
    return {
      policyId: null,
      changedUserGroups: null,
      ajaxLoadedUserGroups: [],
      ALL_POLICIES,
      GROUP_LOAD_URL,
      POLICY_USER_GROUPS,
    }
  },
  watch: {
    policy: {
      handler(newVal) {
        if (newVal.id !== undefined) {
          this.policyId = newVal.id;
        }
      },
      immediate: true
    }
  },
  computed: {
    userGroupOptions: function () {
      return [
        ...this.allGroups.map(function(group) {
          return {
            label: group.title,
            id: group.id,
          }
        }),
        ...this.ajaxLoadedUserGroups
      ];
    },
    userGroups: {
      get: function () {
        if (this.changedUserGroups) {
          return this.changedUserGroups;
        } else {
          return (this.policy.user_groups ? this.policy.user_groups : []);
        }
      },
      set: function (values) {
        this.changedUserGroups = values;
      }
    }
  },
  methods: {
    onChange() {
      const data = {
        'id': this.policyId,
      };
      if (this.policyId === this.POLICY_USER_GROUPS) {
        data.user_groups = this.changedUserGroups;
      }
      this.$emit('change', data);
    },
    setPolicy($event) {
      $event.stopPropagation();
      this.policyId = parseInt($event.target.value, 10);
      this.onChange();
    },
    setSelectedGroups($event) {
      this.changedUserGroups = $event;
      this.onChange();
    },
  }
}
</script>
