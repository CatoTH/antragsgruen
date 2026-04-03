<template>
  <form class="modal-dialog addRestrictedPermissionDialog" method="POST">
    <article class="modal-content">
      <header class="modal-header">
        <button type="button" class="close" data-dismiss="modal" v-t:aria-label="['base', 'abort']"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="editGroupModalLabel" v-t="['admin', 'siteacc_priv_rest_add']"></h4>
      </header>
      <main class="modal-body restrictedAddingForm">
        <div class="restrictedPermissions"><br>
          <strong v-t="['admin', 'siteacc_priv_rest_privs', false, {}, ':']"></strong>
          <label v-for="priv in allPrivilegesMotion" :class="'privilege' + priv.id">
            <input type="checkbox" :checked="isPrivilegeSet(priv.id)" @click="togglePrivilege(priv.id)">
            <span v-if="isDependentPrivilege(priv.id)">↳ </span>
            {{ priv.title }}
          </label>
        </div>

        <div class="restrictedTo">
          <div class="verticalLabels">
            <strong v-t="['admin', 'siteacc_priv_rest_type', false, {}, ':']"></strong><br>
            <label class="motionType">
              <input type="radio" v-model="restrictToType" value="motionType">
              <template v-t="['admin', 'siteacc_priv_rest_mtype']"></template>
            </label>
            <label class="agendaItem">
              <input type="radio" v-model="restrictToType" value="agendaItem">
              <template v-t="['admin', 'siteacc_priv_rest_agenda']"></template>
            </label>
            <label class="tag">
              <input type="radio" v-model="restrictToType" value="tag">
              <template v-t="['admin', 'siteacc_priv_rest_tag']"></template>
            </label>
          </div>

          <div>
            <select class="stdDropdown motionTypes" size="1" v-if="restrictToType === 'motionType'" v-model="restrictToMotionType">
              <option value="">-</option>
              <option v-for="motionType in allMotionTypes" :value="motionType.id">
                {{ motionType.title }}
              </option>
            </select>

            <select class="stdDropdown tags" size="1" v-if="restrictToType === 'tag'" v-model="restrictToTag">
              <option value="">-</option>
              <option v-for="tag in allTags" :value="tag.id">
                {{ tag.title }}
              </option>
            </select>

            <select class="stdDropdown agendaItems" size="1" v-if="restrictToType === 'agendaItem'" v-model="restrictToAgendaItem">
              <option value="">-</option>
              <option v-for="agendaItem in allAgendaItems" :value="agendaItem.id">
                {{ agendaItem.title }}
              </option>
            </select>
          </div>
        </div>
      </main>
      <footer class="modal-footer">
        <button type="button" class="btn btn-default btnCancel" @click="cancel()" v-t="['base', 'abort']"></button>
        <button type="button" class="btn btn-primary btnAdd" @click="add()" :disabled="!canSubmit" v-t="['admin', 'siteacc_priv_rest_add_btn']"></button>
      </footer>
    </article>
  </form>
</template>

<script>
export default {
  props: ['allPrivilegesMotion', 'allMotionTypes', 'allAgendaItems', 'allTags', 'allPrivilegeDependencies'],
  data() {
    return {
      privileges: [],
      restrictToType: null,
      restrictToTag: "",
      restrictToMotionType: "",
      restrictToAgendaItem: ""
    }
  },
  computed: {
    canSubmit: function() {
      return this.privileges.length > 0 && (
          (this.restrictToType === 'tag' && this.restrictToTag !== '') ||
          (this.restrictToType === 'motionType' && this.restrictToMotionType !== '') ||
          (this.restrictToType === 'agendaItem' && this.restrictToAgendaItem !== '')
      );
    }
  },
  methods: {
    add: function () {
      if (!this.canSubmit) {
        return;
      }

      const getMotionType = (motionTypeId) => {
        return this.allMotionTypes.find(motionType => {
          return motionType.id === motionTypeId;
        });
      };
      const getTag = (tagId) => {
        return this.allTags.find(tag => {
          return tag.id === tagId;
        });
      };
      const getAgendaItem = (agendaItemId) => {
        return this.allAgendaItems.find(agendaItem => {
          return agendaItem.id === agendaItemId;
        });
      };

      const permission = {
        motionType: (this.restrictToType === 'motionType' ? getMotionType(parseInt(this.restrictToMotionType, 10)) : null),
        agendaItem: (this.restrictToType === 'agendaItem' ? getAgendaItem(parseInt(this.restrictToAgendaItem, 10)) : null),
        tag: (this.restrictToType === 'tag' ? getTag(parseInt(this.restrictToTag, 10)) : null),
        privileges: this.privileges
      };

      this.$emit('add-restricted', permission);
    },
    cancel: function () {
      this.$emit('cancel-restricted');
    },
    isDependentPrivilege: function (privId) {
      return this.allPrivilegeDependencies[privId.toString()] !== undefined;
    },
    isPrivilegeSet: function (privToFind) {
      return this.privileges.indexOf(privToFind) !== -1;
    },
    addPrivilege: function (privToAdd) {
      this.privileges.push(privToAdd);
      if (this.allPrivilegeDependencies[privToAdd.toString()] !== undefined) {
        this.addPrivilege(this.allPrivilegeDependencies[privToAdd.toString()]);
      }
    },
    removePrivilege: function (privToRemove) {
      this.privileges = this.privileges.filter(priv => priv !== privToRemove);
      Object.keys(this.allPrivilegeDependencies).forEach(parentPriv => {
        if (this.allPrivilegeDependencies[parentPriv] === privToRemove) {
          this.removePrivilege(parseInt(parentPriv, 10));
        }
      });
    },

    togglePrivilege: function (privToFind) {
      if (this.isPrivilegeSet(privToFind)) {
        this.removePrivilege(privToFind);
      } else {
        this.addPrivilege(privToFind);
      }
    }
  }
}
</script>
