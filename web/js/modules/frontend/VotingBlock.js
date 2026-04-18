// @ts-check

import { createApp, h } from '/npm/vue.runtime.esm-browser.prod.js';
import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";
import translateDirective from "/js/vue/Translate.vue.js";
import votingBlockWidget from "/js/vue/voting/VotingBlockWidget.js";
import voteList from "/js/vue/voting/VotingList.js";

export class VotingBlock {
    constructor(el, CONSTANTS, translations) {
        this.element = el;

        const votingInitJson = this.element.getAttribute('data-voting');
        this.createVueWidget(votingInitJson, CONSTANTS, translations);
    }

    createVueWidget(votingInitJson, CONSTANTS, translations) {
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

        translateDirective.registerTranslation("voting", translations);

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
