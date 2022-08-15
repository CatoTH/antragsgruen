declare var Vue: any;

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
        console.log(showAdminLink);

        this.widget = Vue.createApp({
            template: `
                <div class="currentVotings">
                <voting-block-widget v-for="voting in votings" :voting="voting" @vote="vote" :showAdminLink="showAdminLink"></voting-block-widget>
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
                        widget.onReloadedCbs.forEach(cb => {
                            cb(widget.votings);
                        });
                    }).catch(function (err) {
                        alert(err.responseText);
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
