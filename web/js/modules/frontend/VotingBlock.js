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
            // Set where the debate is shown next to this widget: the voting of the debated item is
            // presented there, so it is left out here rather than appearing twice
            excludeDebated = this.element.getAttribute('data-exclude-debated') === '1',
            initialDebatedVoting = this.element.getAttribute('data-debated-voting'),
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
                    // What the channel carries, and what is left of it after filtering: a change of
                    // the debated item has to filter the list again without waiting for a new one
                    allVotings: JSON.parse(votingInitJson),
                    votings: JSON.parse(votingInitJson),
                    debatedVotingBlockId: (initialDebatedVoting ? parseInt(initialDebatedVoting, 10) : null),
                    liveDataHandle: null,
                    debateHandle: null,
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
                    if (excludeDebated && this.debatedVotingBlockId !== null) {
                        filtered = filtered.filter(voting => voting.id !== this.debatedVotingBlockId);
                    }

                    return sortVotings(filtered);
                },
                setVotings: function (votings) {
                    this.allVotings = votings;
                    this.votings = this.filterVotings(votings);
                    this.onReloadedCbs.forEach(cb => {
                        cb(this.votings);
                    });
                },
                /**
                 * The debate moved on to another item, which is being voted on with another voting -
                 * so the one it was on becomes this widget's business, and the new one stops being it.
                 */
                onDebateState: function (state) {
                    const current = (state ? state.current : null);
                    const votingBlockId = (current && current.voting_block ? current.voting_block.id : null);
                    if (votingBlockId === this.debatedVotingBlockId) {
                        return;
                    }
                    this.debatedVotingBlockId = votingBlockId;
                    this.setVotings(this.allVotings);
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
                if (this.debateHandle) {
                    this.debateHandle.unregister();
                }
            },
            created() {
                // The results of a voting that is over do not change any more, so that page is
                // rendered once, declares no channel, and shows the list the server gave it as it is
                if (!channel) {
                    return;
                }
                // The server list goes through the same filter as everything that arrives later -
                // it is the one that knows which of them this page is about
                this.setVotings(this.allVotings);
                this.liveDataHandle = registerListener('user', channel, {
                    onData: votings => this.setVotings(votings),
                });
                if (excludeDebated) {
                    this.debateHandle = registerListener('user', 'debate', {
                        onData: state => this.onDebateState(state),
                    });
                }
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
