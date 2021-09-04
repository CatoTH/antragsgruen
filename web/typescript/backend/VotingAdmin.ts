import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const allVotingData = $element.data('voting');
        const voteSettingsUrl = $element.data('url-vote-settings');
        const addableMotions = $element.data('addable-motions');
        const pollUrl = $element.data('url-poll');

        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     :addableMotions="addableMotions"
                                     @set-status="setStatus"
                                     @save-settings="saveSettings"
                                     @remove-item="removeItem"
                                     @add-item="addItem"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    votings: allVotingData,
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
                        widget.votings = data;
                    }).catch(function (err) {
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
                this.startPolling()
            }
        });
    }
}
