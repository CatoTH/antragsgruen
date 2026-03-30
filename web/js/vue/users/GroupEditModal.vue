<template>
  <div class="modal fade editUserGroupModal editGroupModal" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel" ref="group-edit-modal">
    <group-edit-add-restricted-widget
        v-if="addingRestricted"
        :allPrivilegesMotion="allPrivilegesMotion"
        :allMotionTypes="allMotionTypes"
        :allTags="allTags"
        :allAgendaItems="allAgendaItems"
        :allPrivilegeDependencies="allPrivilegeDependencies"
        @add-restricted="addRestricted"
        @cancel-restricted="cancelAddingRestricted"
    ></group-edit-add-restricted-widget>

    <form class="modal-dialog" method="POST" @submit="save($event)" v-if="!addingRestricted">
      <article class="modal-content">
        <header class="modal-header">
          <button type="button" class="close" data-dismiss="modal" v-t:aria-label="['base', 'abort']"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="editGroupModalLabel">{{ modalTitle }}</h4>
        </header>
        <main class="modal-body" v-if="group && !group.editable">
          <div class="alert alert-info">
            <p v-t="['admin', 'siteacc_groupmodal_system']"></p>
          </div>
        </main>
        <main class="modal-body" v-if="group && group.editable">

          <div class="stdTwoCols">
            <div class="leftColumn" v-t="['admin', 'siteacc_groups_add_name', true]"></div>
            <div class="rightColumn">
              <input type="text" class="form-control inputGroupTitle" v-model="groupTitle">
            </div>
          </div>

          <div class="stdTwoCols">
            <div class="leftColumn" v-t="['admin', 'siteacc_priv_nonmotion', true]"></div>
            <div class="rightColumn">
              <label v-for="priv in allPrivilegesGeneral" :class="'privilege' + priv.id">
                <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                <span v-if="isDependentPrivilege(priv.id)">↳ </span>
                {{ priv.title }}
              </label>
            </div>
          </div>

          <div class="stdTwoCols">
            <div class="leftColumn" v-t="['admin', 'siteacc_priv_motion_all', true]"></div>
            <div class="rightColumn">
              <label v-for="priv in allPrivilegesMotion" :class="'privilege' + priv.id">
                <input type="checkbox" :checked="hasUnrestrictedPrivilege(priv.id)" @click="toggleUnrestrictedPrivilege(priv.id)">
                <span v-if="isDependentPrivilege(priv.id)">↳ </span>
                {{ priv.title }}
              </label>
            </div>
          </div>

          <div class="stdTwoCols">
            <div class="leftColumn" v-t="['admin', 'siteacc_priv_motion_rest', true]"></div>
            <div class="rightColumn">
              <div>
                <ul v-if="setRestrictedPrivileges && setRestrictedPrivileges.length > 0" class="stdNonFormattedList restrictedPrivilegeList">
                  <li v-for="priv in setRestrictedPrivileges">
                    <button class="btn btn-link btnRemove" type="button" @click="removeRestricted(priv)" v-t:title="['base', 'aria_remove']">
                      <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                      <span class="sr-only" v-t="['base', 'aria_remove']"></span>
                    </button>
                    <dl>
                      <dt v-t="['admin', 'siteacc_priv_rest_privs', false, {}, ':']"></dt>
                      <dd>{{ formatPrivilegeIdList(priv.privileges) }}</dd>
                      <dt v-t="['admin', 'siteacc_priv_rest_for', false, {}, ':']"></dt>
                      <dd v-if="priv.motionType">{{ priv.motionType.title }}</dd>
                      <dd v-if="priv.tag">{{ priv.tag.title }}</dd>
                      <dd v-if="priv.agendaItem">{{ priv.agendaItem.title }}</dd>
                    </dl>
                  </li>
                </ul>
                <div v-if="!setRestrictedPrivileges || setRestrictedPrivileges.length === 0" v-t="['admin', 'siteacc_priv_rest_none']"></div>

                <button type="button" class="btn btn-link btnAddRestrictedPermission" @click="startAddingRestricted()">
                  <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                  <template v-t="['admin', 'siteacc_priv_rest_add']"></template>
                </button>
              </div>
            </div>
          </div>

        </main>
        <footer class="modal-footer">
          <a class="changeLogLink" :href="groupLogUrl" v-if="group">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <template v-t="['admin','siteacc_usergroup_log']"></template>
          </a>

          <button type="button" class="btn btn-default btnCancel" data-dismiss="modal" v-t="['base', 'abort']"></button>
          <button type="submit" class="btn btn-primary btnSave" @click="save($event)" v-if="group && group.editable" v-t="['base', 'save']"></button>
        </footer>
      </article>
    </form>
  </div>
</template>

<script>
import translate from "/js/vue/Translate.vue.js";

export default {
  props: ['urlGroupLog', 'allPrivilegesGeneral', 'allPrivilegesMotion', 'allPrivilegeDependencies', 'allMotionTypes', 'allTags', 'allAgendaItems'],
  data() {
    return {
      group: null,
      groupTitle: null,
      addingRestricted: false,
      setNonrestrictedPrivileges: null,
      setRestrictedPrivileges: null
    }
  },
  computed: {
    modalTitle: function () {
      return (this.group ? translate.getTranslation('admin', 'siteacc_groupmodal_title').replace(/%GROUPNAME%/, this.group.title) : '--');
    },
    groupLogUrl: function () {
      return this.urlGroupLog.replace(/%23/g, "#").replace(/###GROUP###/, this.group.id);
    }
  },
  methods: {
    open: function(group) {
      this.group = group;
      this.groupTitle = group.title;

      this.setNonrestrictedPrivileges = [];
      this.setRestrictedPrivileges = [];
      if (group.privileges) group.privileges.forEach(priv => {
        if (priv.motionType !== null || priv.agendaItem !== null || priv.tag !== null) {
          this.setRestrictedPrivileges.push(priv);
        } else {
          this.setNonrestrictedPrivileges.push(...priv.privileges);
        }
      });
      this.addingRestricted = false;

      $(this.$refs['group-edit-modal']).modal("show"); // We won't get rid of jquery/bootstrap anytime soon anyway...
    },
    save: function ($event) {
      if ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }
      if (this.addingRestricted) {
        return;
      }

      const consolidatedPrivileges = Object.assign([], this.setRestrictedPrivileges);
      if (this.setNonrestrictedPrivileges.length > 0) {
        consolidatedPrivileges.push({
          motionType: null,
          agendaItem: null,
          tag: null,
          privileges: this.setNonrestrictedPrivileges
        });
      }

      this.$emit('save-group', this.group.id, this.groupTitle, consolidatedPrivileges);
      $(this.$refs['group-edit-modal']).modal("hide");
    },
    isDependentPrivilege: function (privId) {
      return this.allPrivilegeDependencies[privId.toString()] !== undefined;
    },
    hasUnrestrictedPrivilege: function (privToFind) {
      return this.setNonrestrictedPrivileges.indexOf(privToFind) !== -1;
    },
    addUnrestrictedPrivilege: function (privToAdd) {
      this.setNonrestrictedPrivileges.push(privToAdd);
      if (this.allPrivilegeDependencies[privToAdd.toString()] !== undefined) {
        this.addUnrestrictedPrivilege(this.allPrivilegeDependencies[privToAdd.toString()]);
      }
    },
    removeUnrestrictedPrivilege: function (privToRemove) {
      this.setNonrestrictedPrivileges = this.setNonrestrictedPrivileges.filter(priv => priv !== privToRemove);
      Object.keys(this.allPrivilegeDependencies).forEach(parentPriv => {
        if (this.allPrivilegeDependencies[parentPriv] === privToRemove) {
          this.removeUnrestrictedPrivilege(parseInt(parentPriv, 10));
        }
      });
    },
    toggleUnrestrictedPrivilege: function (privToFind) {
      if (this.hasUnrestrictedPrivilege(privToFind)) {
        this.removeUnrestrictedPrivilege(privToFind);
      } else {
        this.addUnrestrictedPrivilege(privToFind);
      }
    },
    removeRestricted: function(priv) {
      this.setRestrictedPrivileges = this.setRestrictedPrivileges.filter(val => {
        return JSON.stringify(val) !== JSON.stringify(priv);
      });
    },
    startAddingRestricted: function () {
      this.addingRestricted = true;
    },
    cancelAddingRestricted: function () {
      this.addingRestricted = false;
    },
    addRestricted: function (permission) {
      this.addingRestricted = false;
      this.setRestrictedPrivileges.push(permission);
    },
    formatPrivilegeIdList: function (privilegeIds) {
      return privilegeIds.map(privilegeId => {
        return this.allPrivilegesMotion.find(priv => priv.id === privilegeId).title;
      }).join(", ");
    }
  }
}
</script>
