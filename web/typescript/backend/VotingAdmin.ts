import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        this.createVueWidget();
        this.initVotingCreater();
    }

    private createVueWidget() {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const voteSettingsUrl = this.$element.data('url-vote-settings');
        const voteCreateUrl = this.$element.data('vote-create');
        const addableMotions = this.$element.data('addable-motions');
        const pollUrl = this.$element.data('url-poll');
        const votingInitJson = this.$element[0].getAttribute('data-voting');

        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     :addableMotions="addableMotions"
                                     @set-status="setStatus"
                                     @save-settings="saveSettings"
                                     @remove-item="removeItem"
                                     @delete-voting="deleteVoting"
                                     @add-item="addItem"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    votingsJson: null,
                    votings: null,
                    addableMotions,
                    csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
                    pollingId: null
                };
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
                    const url = voteSettingsUrl.replace(/VOTINGBLOCKID/, votingBlockId);
                    $.post(url, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data;
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                setVotingFromJson(data) {
                    if (data === this.votingsJson) {
                        return;
                    }
                    this.votings = JSON.parse(data);
                    this.votingsJson = data;
                },
                setVotingFromObject(data) {
                    this.votings = data;
                    this.votingsJson = null;
                },
                setStatus(votingBlockId, newStatus, organizations) {
                    this._performOperation(votingBlockId, {
                        op: 'update-status',
                        status: newStatus,
                        organizations: organizations.map(orga => { return {
                            id: orga.id,
                            members_present: orga.members_present,
                        }}),
                    });
                },
                saveSettings(votingBlockId, title, assignedMotion) {
                    this._performOperation(votingBlockId, {
                        op: 'save-settings',
                        title,
                        assignedMotion,
                    });
                },
                deleteVoting(votingBlockId) {
                    this._performOperation(votingBlockId, {
                        op: 'delete-voting',
                    });
                },
                createVoting: function (title, assignedMotion) {
                    let postData = {
                        _csrf: this.csrf,
                        title,
                        assignedMotion
                    };
                    const widget = this;
                    $.post(voteCreateUrl, postData, function (data) {
                        if (data.success !== undefined && !data.success) {
                            alert(data.message);
                            return;
                        }
                        widget.votings = data['votings'];

                        window.setTimeout(() => {
                            $("#voting" + data['created_voting']).scrollintoview({top_offset: -100});
                        }, 200);
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                removeItem(votingBlockId, itemType, itemId) {
                    this._performOperation(votingBlockId, {
                        op: 'remove-item',
                        itemType,
                        itemId
                    });
                },
                addItem(votingBlockId, itemDefinition) {
                    this._performOperation(votingBlockId, {
                        op: 'add-item',
                        itemDefinition
                    });
                },
                reloadData: function () {
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.setVotingFromJson(data);
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
                this.setVotingFromJson(votingInitJson);
                this.startPolling()
            }
        });
    }

    private initVotingCreater() {
        const $opener = this.$element.find('.createVotingOpener'),
            $form = this.$element.find('.createVotingHolder');
        $opener.on('click', () => {
            $form.removeClass('hidden');
            $opener.addClass('hidden');
        });
        $form.find('form').on('submit', (ev) => {
            ev.stopPropagation();
            ev.preventDefault();
            this.widget.createVoting($form.find('.settingsTitle').val(), $form.find('.settingsAssignedMotion').val());

            $form.addClass('hidden');
            $opener.removeClass('hidden');
        });
    }
}
