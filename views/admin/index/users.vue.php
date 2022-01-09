<?php

ob_start();
?>
<section>
    <ul class="userAdminList">
        <li v-for="user in users">
            <div class="userInfo">
                {{ user.name }}
            </div>
            <div class="groupsDisplay" v-if="!isGroupChanging(user)">
                {{ userGroupsDisplay(user) }}
                <button class="btn btn-link" @click="setGroupChanging(user)" title="Bearbeiten">
                    <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                </button>
            </div>
            <div class="groupsChange" v-if="isGroupChanging(user)">
                <v-select multiple :options="userGroupOptions" :reduce="group => group.id" :value="selectedGroups(user)"
                          @input="setSelectedGroups($event, user)"></v-select>
            </div>
            <div class="groupsChangeOps" v-if="isGroupChanging(user)">
                <button class="btn btn-link btnLinkAbort" @click="unsetGroupChanging(user)" title="Abbrechen">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                </button>
                <button class="btn btn-link" @click="saveUser(user)" title="Speichern">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                </button>
            </div>
        </li>
    </ul>
</section>
<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('v-select', VueSelect.VueSelect);

    Vue.component('user-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['users', 'groups'],
        data() {
            return {
                changingGroupUsers: [],
                changedUserGroups: [],
            };
        },
        computed: {
            userGroupOptions: function () {
                return this.groups.map(function(group) {
                    return {
                        label: group.title,
                        id: group.id,
                    }
                });
            }
        },
        methods: {
            setGroupChanging: function (user) {
                this.changingGroupUsers.push(user.id);
                Vue.set(this.changedUserGroups, user.id, Object.assign([], user.groups));
            },
            isGroupChanging: function (user) {
                return this.changingGroupUsers.indexOf(user.id) !== -1;
            },
            unsetGroupChanging: function (user) {
                this.changingGroupUsers = this.changingGroupUsers.filter(userId => userId !== user.id);
            },
            userGroupsDisplay: function (user) {
                return this.groups.filter(function (group) {
                    return user.groups.indexOf(group.id) !== -1;
                }).map(function (group) {
                    return group.title;
                }).join(", ");
            },
            selectedGroups: function (user) {
                return this.changedUserGroups[user.id];
            },
            setSelectedGroups: function($event, user) {
                Vue.set(this.changedUserGroups, user.id, $event);
            },
            saveUser: function(user) {
                this.unsetGroupChanging(user);
                this.$emit('save-user-groups', user, this.changedUserGroups[user.id]);
            }
        }
    });

</script>
