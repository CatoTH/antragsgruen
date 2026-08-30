// @ts-check

import { createApp, h } from '/npm/vue.runtime.esm-browser.prod.js';
import { getVotingCommonMixins, sortVotings } from "/js/vue/voting/VotingCommonMixins.js";
import translateDirective from "/js/vue/Translate.vue.js";
import votingBlockWidget from "/js/vue/voting/VotingBlockWidget.js";
import voteList from "/js/vue/voting/VotingList.js";
import { authorizedFetch } from "/js/modules/shared/ApiClient.js";
import { registerListener } from "/js/modules/shared/LiveData.js";

export class VotingBlock {
    constructor(el, CONSTANTS) {
        this.element = el;

        const votingInitJson = this.element.getAttribute('data-voting');
        this.createVueWidget(votingInitJson, CONSTANTS);
    }

    createVueWidget(votingInitJson, CONSTANTS) {
        const commonsMixins = getVotingCommonMixins(CONSTANTS);
        const vueEl = this.element.querySelector(".currentVoting"),
            // Which votings this page shows: everything the channel carries ("all"), the ones that
            // belong to a motion, or the ones that belong to none (the consultation home page)
            channel = this.element.getAttribute('data-channel'),
            filterMotionId = this.element.getAttribute('data-filter-motion'),
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
                    liveDataHandle: null,
                    adminLink,
                    onReloadedCbs: []
                };
            },
            methods: {
                /**
                 * Of everything the channel carries, what this page is about. The backend does not
                 * know that: the collection is the same for every widget on the page.
                 *
                 * Only votings that are open: that is what the channel is defined over, and a live
                 * event about one that is being prepared, was taken offline or is not published yet
                 * only says so much (see VotingPayloadBuilder::buildEveryoneSection) - it is here to
                 * be dropped, not to be shown. The page of results that are over gets its list from
                 * the server and registers no channel, so it never passes through here.
                 */
                filterVotings: function (votings) {
                    let filtered = votings.filter(voting => voting.status === 'open');
                    if (filterMotionId !== null) {
                        const motionId = (filterMotionId === '' ? null : parseInt(filterMotionId, 10));
                        filtered = filtered.filter(voting => voting.assigned_motion_id === motionId);
                    }

                    return sortVotings(filtered);
                },
                setVotings: function (votings) {
                    this.votings = this.filterVotings(votings);
                    this.onReloadedCbs.forEach(cb => {
                        cb(this.votings);
                    });
                },
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
                                return null;
                            }
                            widget.setVotings(data);
                            if (widget.liveDataHandle) {
                                // So that the other widgets and tabs see the vote as well
                                widget.liveDataHandle.refreshNow();
                            }
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
            },
            beforeUnmount() {
                if (this.liveDataHandle) {
                    this.liveDataHandle.unregister();
                }
            },
            created() {
                // The results of a voting that is over do not change any more, so that page is
                // rendered once and declares no channel
                if (!channel) {
                    return;
                }
                this.liveDataHandle = registerListener('user', channel, {
                    onData: votings => this.setVotings(votings),
                });
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
