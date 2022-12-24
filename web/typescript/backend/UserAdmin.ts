declare var Vue: any;

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
        const urlUserGroupLog = this.element.getAttribute('data-url-user-group-log');
        const permissionGlobalEdit = (this.element.getAttribute('data-permission-global-edit') === '1');

        let userWidgetComponent;

        this.widget = Vue.createApp({
            template: `<div class="adminUsers">
                <user-edit-widget
                    :groups="groups"
                    :permissionGlobalEdit="permissionGlobalEdit"
                    ref="user-edit-widget"
                ></user-edit-widget>
                <user-admin-widget :users="users"
                                   :groups="groups"
                                   :urlUserLog="urlUserLog"
                                   :urlUserGroupLog="urlUserGroupLog"
                                   @save-user-groups="saveUserGroups"
                                   @remove-user="removeUser"
                                   @create-user-group="createUserGroup"
                                   @remove-group="removeUserGroup"
                                   @edit-user="editUser"
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
                    urlUserGroupLog,
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
                saveUserGroups(user, groups) {
                    this._performOperation({
                        op: 'save-user-groups',
                        userId: user.id,
                        groups
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
                createUserGroup(groupName) {
                    this._performOperation({
                        op: 'create-user-group',
                        groupName
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
