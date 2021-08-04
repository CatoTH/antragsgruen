import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const allVotingData = $element.data('voting');
        const voteSettingsUrl = $element.data('url-vote-settings');

        console.log(voteSettingsUrl);

        const data = {
            votings: allVotingData,
            csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
        };
        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings"
                                     :voting="voting"
                                     @set-status="setStatus"
                ></voting-admin-widget>
            </div>`,
            data,
            methods: {
                _performOperation: function (votingBlockId, itemType, itemId, additionalProps) {
                    let postData = {
                        _csrf: this.csrf,
                    };
                    if (additionalProps) {
                        postData = (<any>Object).assign(postData, additionalProps);
                    }
                    const widget = this;
                    const url = voteSettingsUrl
                        .replace(/VOTINGBLOCKID/, votingBlockId)
                        .replace(/ITEMTYPE/, itemType)
                        .replace(/ITEMID/, itemId);
                    console.log(url);
                    $.post(url, postData, function (data) {
                        console.log(data.success !== undefined, !data.success);
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
                setStatus(votingBlockId, itemType, itemId, newStatus) {
                    console.log(arguments);
                    this._performOperation(votingBlockId, itemType, itemId, {
                        status: newStatus
                    });
                }
            }
        });
    }
}
