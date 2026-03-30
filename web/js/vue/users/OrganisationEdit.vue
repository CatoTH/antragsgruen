<template>
  <div class="modal fade editOrganisationModal" tabindex="-1" role="dialog" aria-labelledby="editOrganisationModalLabel" ref="organisation-edit-modal">
    <form method="POST" class="modal-dialog">
    <article class="modal-content">
      <header class="modal-header">
        <button type="button" class="close" data-dismiss="modal" v-t:aria-label="['base', 'abort']"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="editOrganisationModalLabel" v-t="['admin', 'siteacc_orgas_opener']"></h4>
      </header>
      <main class="modal-body">
        <div class="alert alert-info">
          <p v-t="['admin', 'con_organisations_hint', true]"></p>
        </div>
        <table>
          <thead>
          <tr>
            <th v-t="['admin', 'siteacc_organs_orga']"></th>
            <th v-if="hasCustomGroups"><template v-t="['admin', 'siteacc_organs_autogroup']"></template>
              <span class="glyphicon glyphicon-info-sign"
                    v-t:aria-label="['admin', 'siteacc_organs_autogroup_tt']"
                    v-tooltip="['admin', 'siteacc_organs_autogroup_tt']"></span>
            </th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(orga, index) in newOrganisations" :key="orga.id">
            <td>
              <input type="text" name="organisation[]" :value="orga.name" class="form-control"
                     @change="setOrganisationName(index, $event.target.value)"
                     @keyup="setOrganisationName(index, $event.target.value)"
              >
            </td>
            <td v-if="hasCustomGroups">
              <select name="autoUserGroups[]" class="stdDropdown" @change="setAutoUserGroup(index, $event.target.value)">
                <option value=""></option>
                <option v-for="group in groups" :value="group.id" :selected="orga.autoUserGroups.indexOf(group.id) > -1">{{ group.title }}</option>
              </select>
            </td>
            <td>
              <button type="button" class="btn btn-link btnRemove" v-t:title="['base', 'aria_remove']" @click="removeOrganisation(index)">
                <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                <span class="sr-only" v-t="['base', 'aria_remove']"></span>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
        <button type="button" class="btn btn-link btnAdd" @click="addOrganisation()">
          <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
          <template v-t="['admin', 'siteacc_organs_add']"></template>
        </button>
      </main>
      <footer class="modal-footer">
        <button type="button" class="btn btn-default btnCancel" data-dismiss="modal" v-t="['base', 'abort']"></button>
        <button type="submit" class="btn btn-primary btnSave" name="saveOrganisations" v-t="['base', 'save']"></button>
      </footer>
    </article>
    </form>
  </div>
</template>

<script>
export default {
  props: ['organisations', 'groups'],
  data() {
    return {
      idcount: 0,
      _newOrganisations: null
    }
  },
  computed: {
    newOrganisations: {
      get: function () {
        if (this._newOrganisations === null) {
          this._newOrganisations = JSON.parse(JSON.stringify(this.organisations));
          for (let i = 0; i < this._newOrganisations.length; i++) {
            this._newOrganisations[i].id = this.idcount++;
          }
        }
        return this._newOrganisations;
      },
      set: function (values) {
        this._newOrganisations = values;
      }
    },
    hasCustomGroups: function () {
      return this.groups.filter(group => group.editable).length > 0;
    }
  },
  methods: {
    open: function () {
      $(this.$refs['organisation-edit-modal']).modal("show");
    },
    addOrganisation: function () {
      this._newOrganisations.push({
        id: this.idcount++,
        name: '',
        autoUserGroups: []
      });
    },
    setOrganisationName: function (index, name) {
      this._newOrganisations[index].name = name;
    },
    setAutoUserGroup: function (index, orga) {
      if (orga > 0) {
        this._newOrganisations[index].autoUserGroups = [orga];
      } else {
        this._newOrganisations[index].autoUserGroups = [];
      }
    },
    removeOrganisation: function(index) {
      this._newOrganisations.splice(index, 1);
    }
  }
}
</script>
