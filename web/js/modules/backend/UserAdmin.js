// @ts-check

import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
import translateDirective from "/js/vue/Translate.vue.js";
import tooltipDirective from '/js/vue/Tooltip.vue.js';
import focusDirective from '/js/vue/Focus.vue.js';
import UserEditModal from '/js/vue/users/UserEditModal.js';
import GroupEditModal from '/js/vue/users/GroupEditModal.js';
import GroupEditAddRestricted from '/js/vue/users/GroupEditAddRestricted.js';
import OrganisationEdit from '/js/vue/users/OrganisationEdit.js';
import UserAdministration from '/js/vue/users/UserAdministration.js';
import Selectize from '/js/vue/Selectize.js';

export class UserAdmin {
    /** @type {import('vue').App} */ widget;
    /** @type {HTMLElement} */element;

    constructor(element) {
        this.element = element;
        this.createVueWidget();

        $('[data-toggle="tooltip"]').tooltip();
    }

    createVueWidget() {
        const vueEl = this.element.querySelector(".userAdmin");
        const userSaveUrl = this.element.getAttribute('data-url-user-save');
        const initUsersJson = this.element.getAttribute('data-users');
        const initGroupsJson = this.element.getAttribute('data-groups');
        const organisations = JSON.parse(this.element.getAttribute('data-organisations'));
        const pollUrl = this.element.getAttribute('data-url-poll');
        const urlUserLog = this.element.getAttribute('data-url-user-log');
        const urlGroupLog = this.element.getAttribute('data-url-user-group-log');
        const permissionGlobalEdit = (this.element.getAttribute('data-permission-global-edit') === '1');
        const nonMotionPrivileges = JSON.parse(this.element.getAttribute('data-non-motion-privileges'));
        const motionPrivileges = JSON.parse(this.element.getAttribute('data-motion-privileges'));
        const agendaItems = JSON.parse(this.element.getAttribute('data-agenda-items'));
        const tags = JSON.parse(this.element.getAttribute('data-tags'));
        const motionTypes = JSON.parse(this.element.getAttribute('data-motion-types'));
        const privilegeDependencies = JSON.parse(this.element.getAttribute('data-privilege-dependencies'));

        let userWidgetComponent;

        this.widget = createApp({
            render() {
                const UserEditWidget = resolveComponent('user-edit-widget');
                const GroupEditWidget = resolveComponent('group-edit-widget');
                const OrganisationEditWidget = resolveComponent('organisation-edit-widget');
                const UserAdminWidget = resolveComponent('user-admin-widget');

                return h('div', { class: 'adminUsers' }, [
                    h(UserEditWidget, {
                        groups: this.groups,
                        organisations: this.organisations,
                        permissionGlobalEdit: this.permissionGlobalEdit,
                        urlUserLog: this.urlUserLog,
                        onSaveUser: (userId, groups, nameGiven, nameFamily, organization, ppReplyTo, voteWeight, newPassword, newAuth, remove2Fa, force2Fa, preventPasswordChange, forcePasswordChange) =>
                            this.saveUser(userId, groups, nameGiven, nameFamily, organization, ppReplyTo, voteWeight, newPassword, newAuth, remove2Fa, force2Fa, preventPasswordChange, forcePasswordChange),
                        onDeleteUser: (userId, msg) => this.deleteUser(userId, msg),
                        ref: 'user-edit-widget',
                    }),
                    h(GroupEditWidget, {
                        urlGroupLog: this.urlGroupLog,
                        allPrivilegesGeneral: this.nonMotionPrivileges,
                        allPrivilegesMotion: this.motionPrivileges,
                        allPrivilegeDependencies: this.privilegeDependencies,
                        allMotionTypes: this.motionTypes,
                        allTags: this.tags,
                        allAgendaItems: this.agendaItems,
                        onSaveGroup: (groupId, groupTitle, privilegeList) => this.saveGroup(groupId, groupTitle, privilegeList),
                        ref: 'group-edit-widget',
                    }),
                    h(OrganisationEditWidget, {
                        organisations: this.organisations,
                        groups: this.groups,
                        ref: 'organisation-edit-widget',
                    }),
                    h(UserAdminWidget, {
                        users: this.users,
                        groups: this.groups,
                        allPrivilegesGeneral: this.nonMotionPrivileges,
                        allPrivilegesMotion: this.motionPrivileges,
                        onRemoveUser: (user) => this.removeUser(user),
                        onEditUser: (user) => this.editUser(user),
                        onSaveUser: (userId, groups, nameGiven, nameFamily, organization, ppReplyTo, voteWeight, newPassword, newAuth, remove2Fa, force2Fa, preventPasswordChange, forcePasswordChange) =>
                            this.saveUser(userId, groups, nameGiven, nameFamily, organization, ppReplyTo, voteWeight, newPassword, newAuth, remove2Fa, force2Fa, preventPasswordChange, forcePasswordChange),
                        onCreateGroup: (groupName) => this.createGroup(groupName),
                        onEditGroup: (group) => this.editGroup(group),
                        onRemoveGroup: (group) => this.removeUserGroup(group),
                        onEditOrganisations: () => this.editOrganisations(),
                        ref: 'user-admin-widget',
                    }),
                ]);
            },
            data() {
                return {
                    usersJson: null,
                    users: null,
                    groupsJson: null,
                    groups: null,
                    csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                    pollingId: null,
                    organisations,
                    urlUserLog,
                    urlGroupLog,
                    nonMotionPrivileges,
                    motionPrivileges,
                    privilegeDependencies,
                    motionTypes,
                    tags,
                    agendaItems,
                    permissionGlobalEdit,
                };
            },
            computed: {
            },
            methods: {
                _performOperation: function (postData) {
                    const widget = this;
                    $.ajax({
                        url: userSaveUrl,
                        type: "POST",
                        data: JSON.stringify(postData),
                        processData: false,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        headers: {"X-CSRF-Token": this.csrf},
                        success: data => {
                            if (data.msg_success) {
                                bootbox.alert(data.msg_success);
                            }
                            if (data.msg_error) {
                                bootbox.alert(data.msg_error);
                            } else {
                                widget.setUserGroups(data.users, data.groups);
                            }
                        }
                    }).catch(function (err) {
                        alert(err.responseText);
                    })
                },
                saveUser(userId, groups, nameGiven, nameFamily, organization, ppReplyTo, voteWeight, newPassword, newAuth, remove2Fa, force2Fa, preventPasswordChange, forcePasswordChange) {
                    this._performOperation({
                        op: 'save-user',
                        userId,
                        nameGiven,
                        nameFamily,
                        organization,
                        groups,
                        ppReplyTo,
                        voteWeight,
                        newPassword,
                        newAuth,
                        remove2Fa,
                        force2Fa,
                        preventPasswordChange,
                        forcePasswordChange
                    });
                },
                deleteUser(userId, msg) {
                    bootbox.confirm(msg, (result) => {
                        if (result) {
                            this._performOperation({
                                op: 'delete-user',
                                userId: userId
                            });
                        }
                    });
                },
                removeUser(user) {
                    this._performOperation({
                        op: 'remove-user',
                        userId: user.id
                    });
                },
                editUser(user) {
                    userWidgetComponent.$refs["user-edit-widget"].open(user);
                },
                setUserGroups(users, groups) {
                    const usersJson = JSON.stringify(users),
                        groupsJson = JSON.stringify(groups);
                    if (usersJson === this.usersJson && groupsJson === this.groupsJson) {
                        return;
                    }
                    this.users = users;
                    this.usersJson = usersJson;
                    this.groups = groups;
                    this.groupsJson = groupsJson;
                },
                createGroup(groupName) {
                    this._performOperation({
                        op: 'create-user-group',
                        groupName
                    });
                },
                editGroup(group) {
                    userWidgetComponent.$refs["group-edit-widget"].open(group);
                },
                saveGroup(groupId, groupTitle, privilegeList) {
                    this._performOperation({
                        op: 'save-group',
                        groupId,
                        groupTitle,
                        privilegeList
                    });
                },
                removeUserGroup(group) {
                    this._performOperation({
                        op: 'remove-group',
                        groupId: group.id
                    });
                },
                editOrganisations() {
                    userWidgetComponent.$refs["organisation-edit-widget"].open();
                },
                reloadData: function () {
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.setUserGroups(data.users, data.groups);
                    }).catch(function (err) {
                        console.error("Could not load user data from backend", err);
                    });
                },
                startPolling: function () {
                    const widget = this;
                    this.pollingId = window.setInterval(function () {
                        widget.reloadData();
                    }, 3000);
                }
            },
            beforeUnmount() {
                window.clearInterval(this.pollingId)
            },
            created() {
                this.setUserGroups(JSON.parse(initUsersJson), JSON.parse(initGroupsJson));
                this.startPolling()
            }
        });

        this.widget.component('user-edit-widget', UserEditModal);
        this.widget.component('group-edit-widget', GroupEditModal);
        this.widget.component('organisation-edit-widget', OrganisationEdit);
        this.widget.component('group-edit-add-restricted-widget', GroupEditAddRestricted);
        this.widget.component('user-admin-widget', UserAdministration);
        this.widget.component('v-selectize', Selectize);

        this.widget.directive('t', translateDirective);
        this.widget.directive('focus', focusDirective);
        this.widget.directive('tooltip', tooltipDirective);

        userWidgetComponent = this.widget.mount(vueEl);

        // Used by tests to control vue-select
        window['userWidget'] = userWidgetComponent;
    }
}
