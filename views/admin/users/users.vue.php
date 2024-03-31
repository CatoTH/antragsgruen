<?php

/** @var \app\controllers\Base $controller */
$controller = $this->context;

ob_start();
?>
<div class="userAdminList">
    <?php
    echo \app\models\layoutHooks\Layout::getAdditionalUserAdministrationVueTemplate($controller->consultation)
    ?>
    <section class="content" aria-label="<?= Yii::t('admin', 'siteacc_accounts_title') ?>">
        <div class="filterHolder">
            <div class="orgaOpenerHolder">
                <button type="button" class="orgaOpener btn btn-link" @click="editOrganisations()">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                    <?= Yii::t('admin', 'siteacc_orgas_opener') ?>
                </button>
            </div>
            <div class="groupFilter">
                <v-selectize @change="setFilterGroup($event)" :options="userGroupFilter"></v-selectize>
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
                    </div>
                    <div class="additional" v-html="formatUserAdditionalData(user)"></div>
                </div>
                <div class="groupsDisplay">
                    {{ userGroupsDisplay(user) }}
                    <button type="button" class="btn btn-link btnEdit" @click="editUser(user)"
                            :title="'<?= Yii::t('admin', 'siteacc_user_edit') ?>'.replace(/%USERNAME%/, user.name)"
                            :aria-label="'<?= Yii::t('admin', 'siteacc_user_edit') ?>'.replace(/%USERNAME%/, user.name)">
                        <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="btn btn-link btnRemove" @click="removeUser(user)"
                            :title="'<?= Yii::t('admin', 'siteacc_user_del') ?>'.replace(/%USERNAME%/, user.name)"
                            :aria-label="'<?= Yii::t('admin', 'siteacc_user_del') ?>'.replace(/%USERNAME%/, user.name)">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
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
                        </div>
                        <div class="additional" v-if="group.description">{{ group.description }}</div>
                        <div class="additional" v-if="!group.description" v-for="formatted in formattedGroupPrivileges(group)">
                            {{ formatted }}
                        </div>
                    </div>
                    <div class="groupActions">
                        <button class="btn btn-link btnEdit" @click="editGroup(group)"
                                :title="'<?= Yii::t('admin', 'siteacc_group_edit') ?>'.replace(/%GROUPNAME%/, group.title)"
                                :aria-label="'<?= Yii::t('admin', 'siteacc_group_edit') ?>'.replace(/%USERNAME%/, group.title)">
                            <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                        </button>

                        <button v-if="group.editable" class="btn btn-link btnRemove" @click="removeGroup(group)"
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

    const LOGIN_STD = 0;

    __setVueComponent('users', 'directive', 'focus', {
        mounted: function (el) {
            el.focus()
        }
    });

    __setVueComponent('users', 'directive', 'tooltip', function (el, binding) {
        $(el).tooltip({
            title: binding.value,
            placement: 'top',
            trigger: 'hover'
        })
    });

    if (window.USER_ADMIN_MIXINS === undefined) {
        window.USER_ADMIN_MIXINS = [];
    }

    __setVueComponent('users', 'component', 'user-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['users', 'groups', 'allPrivilegesGeneral', 'allPrivilegesMotion'],
        mixins: window.USER_ADMIN_MIXINS,
        data() {
            return {
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
            editUser: function (user) {
                this.$emit('edit-user', user)
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
                this.filterGroup = $event && $event.length > 0 ? parseInt($event[0]) : null;
            },
            userGroupsDisplay: function (user) {
                return this.groups.filter(function (group) {
                    return user.groups.indexOf(group.id) !== -1;
                }).map(function (group) {
                    return group.title;
                }).join(", ");
            },
            addGroupToUser: function (user, groupId) {
                if (user.groups.indexOf(groupId) !== -1) {
                    console.warn('Group is already set for this user', groupId, JSON.parse(JSON.stringify(user)));
                } else {
                    user.groups.push(groupId);
                }
                this.$emit('save-user', user.id, user.groups);
            },
            removeGroupFromUser: function (user, groupId) {
                if (user.groups.indexOf(groupId) === -1) {
                    console.warn('User does not have this group', groupId, JSON.parse(JSON.stringify(user)));
                } else {
                    user.groups = user.groups.filter(grp => grp !== groupId);
                }
                this.$emit('save-user', user.id, user.groups);
            },
            formattedGroupPrivileges: function (group) {
                if (!group.privileges) {
                    return [];
                }
                const allPrivs = [...this.allPrivilegesGeneral, ...this.allPrivilegesMotion];
                return group.privileges.map(priv => {
                    let name = priv.privileges.map(privId => {
                        return allPrivs.find(_priv => _priv.id === privId).title;
                    }).join(", ");

                    if (priv.motionType) {
                        name = priv.motionType.title + ": " + name;
                    }
                    if (priv.agendaItem) {
                        name = priv.agendaItem.title + ": " + name;
                    }
                    if (priv.tag) {
                        name = priv.tag.title + ": " + name;
                    }

                    return name;
                });
            },
            addGroupSubmit: function ($event) {
                $event.preventDefault();
                $event.stopPropagation();
                this.$emit('create-group', this.addGroupName);
                this.creatingGroups = false;
            },
            editGroup: function (group) {
                this.$emit('edit-group', group)
            },
            removeGroup: function (group) {
                const widget = this,
                    str = removeGroupConfirmation.replace(/%GROUPNAME%/, group.title);
                bootbox.confirm(str, function(result) {
                    if (result) {
                        widget.$emit('remove-group', group);
                    }
                });
            },
            editOrganisations: function () {
                this.$emit('edit-organisations');
            }
        }
    });

</script>
