declare let Vue: any;

export class VotingBlock {
    private widget: any;
    private widgetComponent: any;

    constructor($element: JQuery) {
        const element = $element[0],
            vueEl = element.querySelector(".currentVoting"),
            votingInitJson = element.getAttribute('data-voting'),
            pollUrl = element.getAttribute('data-url-poll'),
            voteUrl = element.getAttribute('data-url-vote'),
            showAdminLink = element.getAttribute('data-show-admin-link');

        this.widget = Vue.createApp({
            template: `
                <div class="currentVotings">
                <voting-block-widget v-for="voting in votings" :voting="voting" @vote="vote" @abstain="abstain" :showAdminLink="showAdminLink"></voting-block-widget>
                </div>`,
            data() {
                return {
                    votings: JSON.parse(votingInitJson),
                    pollingId: null,
                    showAdminLink,
                    onReloadedCbs: []
                };
            },
            methods: {
                _votePost: function (votingBlockId, postData) {
                    const widget = this;
                    $.ajax({
                        url: voteUrl.replace(/VOTINGBLOCKID/, votingBlockId),
                        type: "POST",
                        data: JSON.stringify(postData),
                        processData: false,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        headers: {"X-CSRF-Token": document.querySelector('head meta[name=csrf-token]').getAttribute('content')},
                        success: data => {
                            if (data.success !== undefined && !data.success) {
                                alert(data.message);
                                return;
                            }
                            widget.votings = data;
                            widget.onReloadedCbs.forEach(cb => {
                                cb(widget.votings);
                            });
                        }
                    });
                },
                vote: function (votingBlockId, itemGroupSameVote, itemType, itemId, vote, votePublic) {
                    this._votePost(votingBlockId, {
                        votes: [{
                            itemGroupSameVote,
                            itemType,
                            itemId,
                            vote,
                            "public": votePublic
                        }]
                    });
                },
                abstain: function (votingBlockId, setAbstention, votePublic) {
                    this._votePost(votingBlockId, {
                        abstention: {
                            abstain: setAbstention,
                            "public": votePublic,
                        }
                    });
                },
                addReloadedCb: function (cb) {
                    this.onReloadedCbs.push(cb);
                },
                reloadData: function () {
                    if (pollUrl === null) {
                        return;
                    }
                    const widget = this;
                    $.get(pollUrl, function (data) {
                        widget.votings = data;
                        widget.onReloadedCbs.forEach(cb => {
                            cb(widget.votings);
                        });
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
            beforeUnmount() {
                window.clearInterval(this.pollingId)
            },
            created() {
                this.startPolling()
            }
        });

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'voting');

        this.widgetComponent = this.widget.mount(vueEl);

        const noneIndicator = document.querySelectorAll('.votingsNoneIndicator')
        this.widgetComponent.addReloadedCb(data => {
            if (data.length === 0) {
                noneIndicator.forEach(node => node.classList.remove('hidden'));
            } else {
                noneIndicator.forEach(node => node.classList.add('hidden'));
            }
        });
    }
}
