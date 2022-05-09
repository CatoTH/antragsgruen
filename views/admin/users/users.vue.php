<?php

ob_start();
?>
<div class="userAdminList">
    <section class="content" aria-label="<?= Yii::t('admin', 'siteacc_accounts_title') ?>">
        <div class="filterHolder">
            <div class="groupFilter">
                <v-select :options="userGroupFilter" :reduce="group => group.id" :value="filterGroup"
                          @input="setFilterGroup($event)" ></v-select>
            </div>
            <div class="usernameFilter">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="<?= Yii::t('admin', 'siteacc_userfilter_place')?>"
                           aria-label="<?= Yii::t('admin', 'siteacc_userfilter_aria')?>"
                           v-model="filterUser" @keydown.esc="filterUser = ''">
                    <span class="input-group-addon" aria-hidden="true"><span class="glyphicon glyphicon-search"></span></span>
                </div>
            </div>
        </div>
        <ul class="userList">
            <li v-for="user in usersFiltered" :class="'user' + user.id">
                <div class="userInfo">
                    <div>
                        <span class="nameUnfiltered" v-if="!filterUser">{{ user.name }}</span>
                        <span class="nameFiltered" v-if="filterUser" v-html="formatUsername(user.name)"></span>
                        <img v-if="user.auth_type === LOGIN_OPENSLIDES" alt="OpenSlides-User" title="OpenSlides-User"
                             src="/img/openslides-logo.svg" class="loginTypeImg">
                    </div>
                    <div class="additional" v-html="formatUserAdditionalData(user)"></div>
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
                            :aria-label="'<?= Yii::t('admin', 'siteacc_groups_del') ?>'.replace(/%USERNAME%/, user.name)">
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
    <section aria-labelledby="userGroupsAdminTitle">
        <h2 class="green" id="userGroupsAdminTitle"><?= Yii::t('admin', 'siteacc_groups_title') ?></h2>
        <div class="content">
            <ul class="groupList">
                <li v-for="group in sortedGroups" :class="'group' + group.id">
                    <div class="groupInfo">
                        <div class="name">
                            {{ group.title }}
                            <img v-if="group.auth_type === LOGIN_OPENSLIDES" alt="OpenSlides" title="OpenSlides"
                                 src="/img/openslides-logo.svg" class="loginTypeImg">
                        </div>
                        <div class="additional" v-if="group.description">{{ group.description }}</div>
                    </div>
                    <div class="groupActions" v-if="group.deletable">
                        <button class="btn btn-link btnRemove" @click="removeGroup(group)"
                                :title="'<?= Yii::t('admin', 'siteacc_group_del') ?>'.replace(/%GROUPNAME%/, group.title)"
                                :aria-label="'<?= Yii::t('admin', 'siteacc_group_del') ?>'.replace(/%GROUPNAME%/, group.title)">
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                        </button>
                    </div>
                </li>
            </ul>
            <button class="btn btn-link btnGroupCreate" type="button" @click="creatingGroups = true" v-if="!creatingGroups">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('admin', 'siteacc_groups_add_open') ?>
            </button>
            <form @submit="addGroupSubmit($event)" class="addGroupForm" v-if="creatingGroups">
                <label class="addGroupName">
                    <?= Yii::t('admin', 'siteacc_groups_add_name') ?>:<br>
                    <input type="text" v-model="addGroupName" class="form-control" ref="addGroupName" v-focus required>
                </label>
                <div class="actions">
                    <button type="submit" class="btn btn-primary btnSave">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        <?= Yii::t('base', 'save') ?>
                    </button>
                    <button type="button" class="btn btn-link btnCancel" @click="creatingGroups = false" title="<?= Yii::t('base', 'abort') ?>">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
<?php
$html = ob_get_clean();
?>

<script>
    const removeUserConfirmation = <?= json_encode(Yii::t('admin', 'siteacc_userdel_confirm')) ?>;
    const removeGroupConfirmation = <?= json_encode(Yii::t('admin', 'siteacc_groupdel_confirm')) ?>;
    const showAllGroups = <?= json_encode(Yii::t('admin', 'siteacc_userfilter_allgr')) ?>;

    const LOGIN_OPENSLIDES = <?= \app\models\settings\Site::LOGIN_OPENSLIDES ?>;

    Vue.component('v-select', VueSelect.VueSelect);
    Vue.directive('focus', {
        inserted: function (el) {
            el.focus()
        }
    });

    Vue.component('user-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['users', 'groups'],
        data() {
            return {
                changingGroupUsers: [],
                changedUserGroups: [],
                creatingGroups: false,
                addGroupName: '',
                filterUser: '',
                filterGroup: ''
            };
        },
        computed: {
            userGroupFilter: function () {
                const groupsUsers = {};
                this.users.forEach(user => {
                    user.groups.forEach(groupId => {
                        if (groupsUsers[groupId.toString()] === undefined) {
                            groupsUsers[groupId.toString()] = 1;
                        } else {
                            groupsUsers[groupId.toString()]++;
                        }
                    });
                });
                const sortedGroups = this.sortedGroups;

                return [
                    {
                        label: showAllGroups,
                        id: '',
                    },
                    ...sortedGroups.map(function(group) {
                        let title = group.title;
                        if (groupsUsers[group.id.toString()] !== undefined) {
                            title += ' (' + groupsUsers[group.id.toString()] + ')';
                        }
                        return {
                            label: title,
                            id: group.id,
                        }
                    })
                ];
            },
            userGroupOptions: function () {
                return this.groups.map(function(group) {
                    return {
                        label: group.title,
                        id: group.id,
                    }
                });
            },
            usersFiltered: function () {
                const searchTerm = this.filterUser.toLowerCase();
                let users = this.users;
                if (searchTerm !== '') {
                    users = this.users.filter(function(user) {
                        return user.name.toLowerCase().indexOf(searchTerm) !== -1
                            || (user.email && user.email.toLowerCase().indexOf(searchTerm) !== -1)
                            || (user.organization && user.organization.toLowerCase().indexOf(searchTerm) !== -1)
                    });
                }
                if (this.filterGroup > 0) {
                    const filterGroup = this.filterGroup;
                    users = users.filter(function(user) {
                        return user.groups.indexOf(filterGroup) !== -1;
                    });
                }
                return users.sort(function (user1, user2) {
                    return user1.name.localeCompare(user2.name);
                });
            },
            sortedGroups: function () {
                return this.groups.sort((group1, group2) => {
                    const name1 = group1.title.toUpperCase();
                    const name2 = group2.title.toUpperCase(); // ignore upper and lowercase
                    if (name1 < name2) {
                        return -1;
                    } else if (name1 > name2) {
                        return 1;
                    } else {
                        return 0;
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
            escapeHtml: function (text) {
                return text.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            },
            formatSubstrOcurrences: function (str, needle) {
                const normalizedStr = this.escapeHtml(str.toLowerCase()),
                    normalizedNeedle = this.escapeHtml(needle.toLowerCase());
                let pos = normalizedStr.lastIndexOf(normalizedNeedle);
                while (pos !== -1) {
                    str = str.substr(0, pos) + '<strong>' + str.substr(pos, normalizedNeedle.length) + '</strong>' + str.substr(pos + normalizedNeedle.length);
                    if (pos === 0) {
                        // Prevent an endless loop, as "test".lastIndexOf("t", -1) === 0
                        pos = -1;
                    } else {
                        pos = normalizedStr.lastIndexOf(normalizedNeedle, pos - 1);
                    }
                }
                return str;
            },
            formatUsername: function (username) {
                if (this.filterUser === '') {
                    return '<strong>' + this.escapeHtml(username) + '</strong>';
                } else {
                    return this.formatSubstrOcurrences(username, this.filterUser);
                }
            },
            getAuthUsername: function (user) {
                const parts = user.auth.split(':');
                if (parts[0] === 'openid') {
                    const parts2 = user.auth.split('/');
                    return parts2[parts2.length - 1];
                } else {
                    if (parts.length > 1) {
                        return parts[1];
                    } else {
                        return user.email;
                    }
                }
            },
            formatUserAdditionalData: function (user) {
                const username = this.getAuthUsername(user);
                let str = username;
                if (username && user.organization) {
                    str += ", ";
                }
                if (user.organization) {
                    str += user.organization;
                }

                if (this.filterUser === '') {
                    return this.escapeHtml(str);
                } else {
                    const pos = str.toLowerCase().indexOf(this.filterUser.toLowerCase());
                    if (pos === -1) {
                        return str;
                    }
                    return this.escapeHtml(str.substr(0, pos)) +
                        '<strong>' + this.escapeHtml(str.substr(pos, this.filterUser.length)) + '</strong>' +
                        this.escapeHtml(str.substr(pos + this.filterUser.length));
                }
            },
            setFilterGroup: function ($event) {
                this.filterGroup = $event;
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
            addSelectedGroup: function(groupId, user) {
                let groups = this.selectedGroups(user);
                groups.push(groupId);
                this.setSelectedGroups(groups, user);
            },
            saveUser: function(user) {
                this.unsetGroupChanging(user);
                this.$emit('save-user-groups', user, this.changedUserGroups[user.id]);
            },
            addGroupSubmit: function ($event) {
                $event.preventDefault();
                $event.stopPropagation();
                this.$emit('create-user-group', this.addGroupName);
                this.creatingGroups = false;
            },
            removeGroup: function (group) {
                const widget = this,
                    str = removeGroupConfirmation.replace(/%GROUPNAME%/, group.title);
                bootbox.confirm(str, function(result) {
                    if (result) {
                        widget.$emit('remove-group', group);
                    }
                });
            }
        }
    });

</script>
