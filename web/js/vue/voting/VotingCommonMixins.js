// @ts-check

import translate from "/js/vue/Translate.vue.js";

// The payload the widgets work with is documented in docs/openapi.yaml (VotingBlockUser /
// VotingBlockAdmin). Two things about it shape everything below:
// - What is voted on together is an "item group"; an item voted on by itself gets a group of its
//   own. Results and single votes hang off the group, so there is one code path, not two.
// - Whether the results and the single votes are part of the payload at all is decided by the
//   backend. A null means "not for you", an empty list means "nothing yet" - never confuse them.

/**
 * The order the administration gave the votings, as the backend sorts them (Factory::sortVotingBlocks).
 * A poll answers in that order, but a live event describes one voting at a time, so a client that
 * has stopped polling has to be able to put a re-sorted list back in order itself.
 *
 * @param {any[]} votings
 * @returns {any[]} a sorted copy
 */
export function sortVotings(votings) {
    return votings.slice().sort((voting1, voting2) => {
        if (voting1.position !== voting2.position) {
            return voting2.position - voting1.position;
        }
        return voting2.id - voting1.id;
    });
}

export function getVotingCommonMixins(constants) {
    return {
        data() {
            return Object.assign({
                timerId: null,
                timeOffset: 0, // milliseconds the browser is ahead of the server time
                remainingVotingTime: null
            }, constants);
        },
        computed: {
            /**
             * The item groups of this voting, each with the items it is voted on with, resolved from
             * the flat list of items.
             */
            groups: function () {
                const itemsByRef = {};
                this.voting.items.forEach(item => {
                    itemsByRef[item.type + '-' + item.id] = item;
                });

                return this.voting.item_groups.map(group => Object.assign({}, group, {
                    resolvedItems: group.items
                        .map(ref => itemsByRef[ref.type + '-' + ref.id])
                        .filter(item => item !== undefined)
                }));
            },
            votingHasMajority: function () {
                return this.voting.has_majority;
            },
            votingIsPresenceCall: function () {
                return this.voting.is_presence_call;
            },
            votingHasQuorum: function () {
                return this.voting.quorum !== null && this.voting.quorum !== undefined;
            },
            isPreparing: function () {
                return this.voting.status === 'preparing';
            },
            isOpen: function () {
                return this.voting.status === 'open';
            },
            isClosed: function () {
                return this.voting.status === 'closed_published' || this.voting.status === 'closed_unpublished';
            },
            hasVotingTime: function () {
                return this.voting.voting_time > 0;
            },
            formattedRemainingTime: function () {
                const minutes = Math.floor(this.remainingVotingTime / 60);
                let seconds = this.remainingVotingTime - minutes * 60;
                if (seconds < 10) {
                    seconds = "0" + seconds;
                }

                return minutes + ":" + seconds;
            },
            hasGeneralAbstention: function () {
                return !!this.voting.abstention && this.voting.abstention.enabled;
            }
        },
        methods: {
            /**
             * The counted votes of a group, as one table of answer => number of votes.
             *
             * "counts" is a list per organization, which only a plugin that counts by organization
             * ever fills with more than the one default entry. There is no way to show several of
             * them in this table, and showing the first as if it were the whole would be a wrong
             * number rather than a missing one - so a payload with several is treated as having no
             * result to show here, which is what the widgets did before the counts became a list.
             *
             * @param {object} group
             * @returns {object|null} the counted votes of a group, or null if there are none to show
             */
            getResults: function (group) {
                if (!group.results || group.results.counts.length !== 1) {
                    return null;
                }
                return group.results.counts[0];
            },
            getVotesForAnswer: function (group, apiId) {
                const results = this.getResults(group);
                if (!results) {
                    return null;
                }
                const found = results.answers.find(count => count.answer === apiId);

                return found ? found.votes : 0;
            },
            getAbsoluteNumberOfVotes(group) {
                const results = this.getResults(group);
                if (!results) {
                    return 0;
                }

                return results.answers.reduce((sum, count) => sum + count.votes, 0);
            },
            hasResults: function (group) {
                return this.getResults(group) !== null;
            },
            itemIsAccepted: function (group) {
                return group.resolvedItems[0].result === 'accepted';
            },
            itemIsRejected: function (group) {
                return group.resolvedItems[0].result === 'rejected';
            },
            itemIsQuorumReached: function (group) {
                return group.resolvedItems[0].result === 'quorum_reached';
            },
            itemIsQuorumFailed: function (group) {
                return group.resolvedItems[0].result === 'quorum_missed';
            },
            quorumCounter: function (group) {
                const quorum = group.results ? group.results.quorum : null;
                if (!quorum || quorum.votes === null) {
                    return quorum ? quorum.current_label : null;
                }
                const template = translate.getTranslation("voting", "quorum_counter");

                return template.replace(/%QUORUM%/, this.voting.quorum.target).replace(/%CURRENT%/, quorum.votes);
            },
            /** Whether this reader may see who voted how - not whether anyone has voted yet */
            hasVoteList: function (group) {
                return group.single_votes !== null && group.single_votes !== undefined;
            },
            isVoteListShown: function (group) {
                return this.shownVoteLists.indexOf(group.id) !== -1;
            },
            showVoteList: function (group) {
                this.shownVoteLists.push(group.id);
            },
            hideVoteList: function (group) {
                this.shownVoteLists = this.shownVoteLists.filter(id => id !== group.id);
            },
            recalcTimeOffset: function (serverTime) {
                const browserTime = (new Date()).getTime();
                this.timeOffset = browserTime - serverTime.getTime();
            },
            recalcRemainingTime: function () {
                if (!this.voting.opened_at) {
                    return;
                }
                const startedTs = (new Date(this.voting.opened_at)).getTime();
                const currentTs = (new Date()).getTime() - this.timeOffset;
                const secondsPassed = Math.round((currentTs - startedTs) / 1000);

                this.remainingVotingTime = this.voting.voting_time - secondsPassed;
            },
            startPolling: function () {
                this.recalcTimeOffset(new Date());

                const widget = this;

                this.timerId = window.setInterval(function () {
                    widget.recalcRemainingTime();
                }, 100);
            },
            stopPolling: function () {
                window.clearInterval(this.timerId);
            }
        }
    };
}
