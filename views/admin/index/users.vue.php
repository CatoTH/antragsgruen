<?php

ob_start();
?>
<section>
    <ul class="userAdminList">
        <li v-for="user in users" :class="'user' + user.id">
            <div class="userInfo">
                <div class="name">{{ user.name }}</div>
                <div class="additional">{{ userAdditionalData(user) }}</div>
            </div>
            <div class="groupsDisplay" v-if="!isGroupChanging(user)">
                {{ userGroupsDisplay(user) }}
                <button class="btn btn-link btnEdit" @click="setGroupChanging(user)"
                        :title="'<?= Yii::t('admin', 'siteacc_groups_edit') ?>'.replace(/%USERNAME%/, user.name)"
                        :aria-label="'<?= Yii::t('admin', 'siteacc_groups_edit') ?>'.replace(/%USERNAME%/, user.name)">
                    <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                </button>
                <button class="btn btn-link btnRemove" @click="removeUser(user)"
                        :title="'<?= Yii::t('admin', 'siteacc_groups_del') ?>'.replace(/%USERNAME%/, user.name)"
                        :aria-label="'<?= Yii::t('admin', 'siteacc_groups_edit') ?>'.replace(/%USERNAME%/, user.name)">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                </button>
            </div>
            <div class="groupsChange" v-if="isGroupChanging(user)">
                <v-select multiple :options="userGroupOptions" :reduce="group => group.id" :value="selectedGroups(user)"
                          @input="setSelectedGroups($event, user)"></v-select>
            </div>
            <div class="groupsChangeOps" v-if="isGroupChanging(user)">
                <button class="btn btn-link btnLinkAbort" @click="unsetGroupChanging(user)" title="<?= Yii::t('base', 'abort') ?>">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                </button>
                <button class="btn btn-link" @click="saveUser(user)" title="<?= Yii::t('base', 'save') ?>">
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
    const removeUserConfirmation = <?= json_encode(Yii::t('admin', 'siteacc_del_confirm')) ?>;
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
            removeUser: function (user) {
                const widget = this,
                    str = removeUserConfirmation.replace(/%USERNAME%/, user.name);
                bootbox.confirm(str, function(result) {
                    if (result) {
                        widget.$emit('remove-user', user);
                    }
                });
            },
            userGroupsDisplay: function (user) {
                return this.groups.filter(function (group) {
                    return user.groups.indexOf(group.id) !== -1;
                }).map(function (group) {
                    return group.title;
                }).join(", ");
            },
            userAdditionalData: function (user) {
                let str = user.email;
                if (user.organization) {
                    str += ", " + user.organization;
                }
                return str;
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
