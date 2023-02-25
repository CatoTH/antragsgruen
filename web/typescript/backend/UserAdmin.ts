declare let Vue: any;

export class UserAdmin {
    private widget;
    private element: HTMLElement;

    constructor($element: JQuery) {
        this.element = $element[0];
        this.createVueWidget();

        $('[data-toggle="tooltip"]').tooltip();
    }

    private createVueWidget() {
        const vueEl = this.element.querySelector(".userAdmin");
        const userSaveUrl = this.element.getAttribute('data-url-user-save');
        const initUsersJson = this.element.getAttribute('data-users');
        const initGroupsJson = this.element.getAttribute('data-groups');
        const pollUrl = this.element.getAttribute('data-url-poll');
        const urlUserLog = this.element.getAttribute('data-url-user-log');
        const urlGroupLog = this.element.getAttribute('data-url-user-group-log');
        const permissionGlobalEdit = (this.element.getAttribute('data-permission-global-edit') === '1');

        let userWidgetComponent;

        this.widget = Vue.createApp({
            template: `<div class="adminUsers">
                <user-edit-widget
                    :groups="groups"
                    :permissionGlobalEdit="permissionGlobalEdit"
                    :urlUserLog="urlUserLog"
                    @save-user="saveUser"
                    ref="user-edit-widget"
                ></user-edit-widget>
                <group-edit-widget
                    :urlGroupLog="urlGroupLog"
                    @save-group="saveGroup"
                    ref="group-edit-widget"
                ></group-edit-widget>
                <user-admin-widget
                    :users="users"
                    :groups="groups"
                    @remove-user="removeUser"
                    @edit-user="editUser"
                    @save-user="saveUser"
                    @create-group="createGroup"
                    @edit-group="editGroup"
                    @remove-group="removeUserGroup"
                    ref="user-admin-widget"
                ></user-admin-widget>
            </div>`,
            data() {
                return {
                    usersJson: null,
                    users: null,
                    groupsJson: null,
                    groups: null,
                    csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                    pollingId: null,
                    urlUserLog,
                    urlGroupLog,
                    permissionGlobalEdit,
                };
            },
            computed: {
            },
            methods: {
                _performOperation: function (additionalProps) {
                    let postData = {
                        _csrf: this.csrf,
                    };
                    postData = Object.assign(postData, additionalProps);
                    const widget = this;
                    $.post(userSaveUrl, postData, function (data) {
                        if (data.msg_success) {
                            bootbox.alert(data.msg_success);
                        }
                        if (data.msg_error) {
                            bootbox.alert(data.msg_error);
                        } else {
                            widget.setUserGroups(data.users, data.groups);
                        }
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                saveUser(userId, groups, nameGiven, nameFamily, organization, newPassword) {
                    this._performOperation({
                        op: 'save-user',
                        userId,
                        nameGiven,
                        nameFamily,
                        organization,
                        groups,
                        newPassword
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
                saveGroup(groupId, groupTitle) {
                    this._performOperation({
                        op: 'save-group',
                        groupId,
                        groupTitle
                    });
                },
                removeUserGroup(group) {
                    this._performOperation({
                        op: 'remove-group',
                        groupId: group.id
                    });
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

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'users');

        userWidgetComponent = this.widget.mount(vueEl);

        // Used by tests to control vue-select
        window['userWidget'] = userWidgetComponent;
    }
}
