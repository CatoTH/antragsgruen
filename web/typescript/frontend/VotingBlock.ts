import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingBlock {
    private widget;

    constructor($element: JQuery) {
        const element = $element[0],
            vueEl = element.querySelector(".currentVoting"),
            votingInitJson = element.getAttribute('data-voting'),
            pollUrl = element.getAttribute('data-url-poll'),
            voteUrl = element.getAttribute('data-url-vote');

        this.widget = new Vue({
            el: vueEl,
            template: `
                <div class="currentVotings">
                <voting-block-widget v-for="voting in votings" :voting="voting" @vote="vote"></voting-block-widget>
                </div>`,
            data() {
                return {
                    votings: JSON.parse(votingInitJson),
                    pollingId: null
                };
            },
            methods: {
                vote: function (votingBlockId, itemGroupSameVote, itemType, itemId, vote, votePublic) {
                    const postData = {
                        _csrf: document.querySelector('head meta[name=csrf-token]').getAttribute('content'),
                        votes: [{
                            itemGroupSameVote,
                            itemType,
                            itemId,
                            vote,
                            "public": votePublic
                        }]
                    };
                    const widget = this;
                    const url = voteUrl.replace(/VOTINGBLOCKID/, votingBlockId);
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
                reloadData: function () {
                    if (pollUrl === null) {
                        return;
                    }
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
