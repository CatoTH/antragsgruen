import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingBlock {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".currentVoting");
        const allVotingData = $element.data('voting');
        const pollUrl = $element.data('url-poll');
        const voteUrl = $element.data('url-vote');

        this.widget = new Vue({
            el: $vueEl[0],
            template: `
                <div class="currentVotings">
                <voting-block-widget v-for="voting in votings" :voting="voting" @vote="vote"></voting-block-widget>
                </div>`,
            data() {
                return {
                    votings: allVotingData,
                    pollingId: null
                };
            },
            methods: {
                vote: function (votingBlockId, itemType, itemId, vote) {
                    console.log(arguments);
                    console.log(pollUrl);
                    console.log(voteUrl);
                    const postData = {
                        _csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
                        votes: [{
                            itemType,
                            itemId,
                            vote
                        }]
                    };
                    const widget = this;
                    const url = voteUrl
                        .replace(/VOTINGBLOCKID/, votingBlockId)
                        .replace(/ITEMTYPE/, itemType)
                        .replace(/ITEMID/, itemId);
                    $.post(url, postData, function (data) {
                        widget.votings = data;
                    }).catch(function (err) {
                        alert(err.responseText);
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
