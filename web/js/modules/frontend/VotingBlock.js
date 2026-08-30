// @ts-check

import { createApp, h } from '/npm/vue.runtime.esm-browser.prod.js';
import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";
import translateDirective from "/js/vue/Translate.vue.js";
import votingBlockWidget from "/js/vue/voting/VotingBlockWidget.js";
import voteList from "/js/vue/voting/VotingList.js";
import { authorizedFetch } from "/js/modules/shared/ApiClient.js";

export class VotingBlock {
    constructor(el, CONSTANTS) {
        this.element = el;

        const votingInitJson = this.element.getAttribute('data-voting');
        this.createVueWidget(votingInitJson, CONSTANTS);
    }

    createVueWidget(votingInitJson, CONSTANTS) {
        const commonsMixins = getVotingCommonMixins(CONSTANTS);
        const vueEl = this.element.querySelector(".currentVoting"),
            pollUrl = this.element.getAttribute('data-url-poll'),
            voteUrl = this.element.getAttribute('data-url-vote'),
            adminLink = this.element.getAttribute('data-admin-link');

        /** @type {import('vue').App} */
        const widget = createApp({
            render() {
                return h(
                    'div',
                    { class: 'currentVotings' },
                    this.votings.map(voting =>
                        h(votingBlockWidget, {
                            voting,
                            adminLink: this.adminLink,
                            onVote: this.vote,
                            onAbstain: this.abstain
                        })
                    )
                );
            },
            data() {
                return {
                    votings: JSON.parse(votingInitJson),
                    pollingId: null,
                    adminLink,
                    onReloadedCbs: []
                };
            },
            methods: {
                _votePost: function (votingBlockId, postData) {
                    const widget = this;
                    authorizedFetch(voteUrl.replace(/VOTINGBLOCKID/, votingBlockId), {
                        method: "POST",
                        headers: {"Content-Type": "application/json; charset=utf-8"},
                        body: JSON.stringify(postData),
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success !== undefined && !data.success) {
                                alert(data.message);
                                return;
                            }
                            widget.votings = data;
                            widget.onReloadedCbs.forEach(cb => {
                                cb(widget.votings);
                            });
                            return null;
                        })
                        .catch(err => {
                            console.error("Could not submit the vote", err);
                        });
                },
                // How public a vote becomes is decided by the backend alone - the voting promised
                // it when it was opened, and nothing the browser sends can change that
                vote: function (votingBlockId, groupId, vote) {
                    this._votePost(votingBlockId, {
                        votes: [{groupId, vote}]
                    });
                },
                abstain: function (votingBlockId, setAbstention) {
                    this._votePost(votingBlockId, {
                        abstention: {abstain: setAbstention}
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
                    authorizedFetch(pollUrl)
                        .then(response => response.json())
                        .then(data => {
                            widget.votings = data;
                            widget.onReloadedCbs.forEach(cb => {
                                cb(widget.votings);
                            });
                            return null;
                        })
                        .catch(function (err) {
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

        widget.directive('t', translateDirective);
        widget.mixin(commonsMixins);
        widget.component('vote-list', voteList);

        const widgetComponent = widget.mount(vueEl);

        const noneIndicator = document.querySelectorAll('.votingsNoneIndicator')
        widgetComponent.addReloadedCb(data => {
            if (data.length === 0) {
                noneIndicator.forEach(node => node.classList.remove('hidden'));
            } else {
                noneIndicator.forEach(node => node.classList.add('hidden'));
            }
        });
    }
}
