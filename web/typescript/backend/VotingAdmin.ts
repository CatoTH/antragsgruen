import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const allVotingData = $element.data('voting');
        const voteSettingsUrl = $element.data('url-vote-settings');
        const pollUrl = $element.data('url-poll');

        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     @set-status="setStatus"
                                     @remove-item="removeItem"
                ></voting-admin-widget>
            </div>`,
            data() {
                return {
                    votings: allVotingData,
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
                        op: 'update',
                        status: newStatus,
                        organizations: organizations.map(orga => { return {
                            id: orga.id,
                            members_present: orga.members_present,
                        }}),
                    });
                },
                removeItem(votingBlockId, itemType, itemId) {
                    this._performOperation(votingBlockId, {
                        op: 'remove-item',
                        itemType,
                        itemId
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
