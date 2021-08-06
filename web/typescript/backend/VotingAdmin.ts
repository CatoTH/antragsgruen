import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const allVotingData = $element.data('voting');
        const voteSettingsUrl = $element.data('url-vote-settings');
        const pollUrl = $element.data('url-poll');

        console.log(JSON.parse(JSON.stringify(allVotingData)));
        console.log(pollUrl);

        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     @set-status="setStatus"
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
                        console.log("returned", data);
                    }).catch(function (err) {
                        alert(err.responseText);
                    });
                },
                setStatus(votingBlockId, newStatus) {
                    console.log(arguments);
                    this._performOperation(votingBlockId, {
                        status: newStatus
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
