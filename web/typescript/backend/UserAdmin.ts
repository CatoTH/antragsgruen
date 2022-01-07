import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

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

        this.widget = new Vue({
            el: vueEl,
            template: `<div class="adminUsers">
                <user-admin-widget :users="users"
                                   :groups="groups"
                                     @save-user="saveUser"
                ></user-admin-widget>
            </div>`,
            data() {
                return {
                    usersJson: null,
                    users: null,
                    groupsJson: null,
                    groups: null,
                    csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                    pollingId: null
                };
            },
            computed: {
            },
            methods: {
                _performOperation: function (votingBlockId, additionalProps) {
                    let postData = {
                        _csrf: this.csrf,
                    };
                    if (additionalProps) {
                        postData = Object.assign(postData, additionalProps);
                    }
                    const widget = this;
                    $.post(userSaveUrl, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data;
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                saveUser(userId) {
                    this._performOperation({
                        op: 'save-user',
                        userId,
                    });
                },
                setUserGroupsFromJson(users, groups) {
                    if (users === this.usersJson && groups === this.groupsJson) {
                        return;
                    }
                    this.users = JSON.parse(users);
                    this.usersJson = users;
                    this.groups = JSON.parse(groups);
                    this.groupsJson = groups;
                },
                reloadData: function () {
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.setUserGroupsFromJson(data); // @TODO
                    }, 'text').catch(function (err) {
                        console.error("Could not load voting data from backend", err);
                    });
                },
                startPolling: function () {
                    const widget = this;
                    this.pollingId = window.setInterval(function () {
                        widget.reloadData();
                    }, 3000);
                }
            },
            beforeDestroy() {
                window.clearInterval(this.pollingId)
            },
            created() {
                this.setUserGroupsFromJson(initUsersJson, initGroupsJson);
                this.startPolling()
            }
        });
    }
}
