// @ts-check

import translate from "/js/vue/Translate.vue.js";

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
            groupedVotings: function () {
                const knownGroupIds = {};
                const allGroups = [];
                this.voting.items.forEach(function (item) {
                    if (item.item_group_same_vote) {
                        if (knownGroupIds[item.item_group_same_vote] !== undefined) {
                            allGroups[knownGroupIds[item.item_group_same_vote]].push(item);
                        } else {
                            knownGroupIds[item.item_group_same_vote] = allGroups.length;
                            allGroups.push([item]);
                        }
                    } else {
                        allGroups.push([item]);
                    }
                });
                return allGroups;
            },
            votingHasMajority: function () {
                // Used for the currently running vote as it is
                return this.voting.answers_template === this.ANSWER_TEMPLATE_YES_NO_ABSTENTION || this.voting.answers_template === this.ANSWER_TEMPLATE_YES_NO;
            },
            votingIsPresenceCall: function () {
                return (this.voting.answers_template === this.ANSWER_TEMPLATE_PRESENT);
            },
            votingHasQuorum: function () {
                return this.voting.quorum_type !== this.QUORUM_TYPE_NONE && this.voting.quorum_type !== null;
            },
            isPreparing: function () {
                return this.voting.status === this.STATUS_PREPARING;
            },
            isOpen: function () {
                return this.voting.status === this.STATUS_OPEN;
            },
            isClosed: function () {
                return this.voting.status === this.STATUS_CLOSED_PUBLISHED || this.voting.status === this.STATUS_CLOSED_UNPUBLISHED;
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
                return this.voting.has_general_abstention;
            }
        },
        methods: {
            getAbsoluteNumberOfVotes(resultList) {
                let absolute = 0;
                this.voting.answers.forEach(answer => {
                    absolute += resultList[answer.api_id];
                });
                return absolute;
            },
            itemIsAccepted: function (groupedItem) {
                return groupedItem[0].voting_status === this.VOTING_STATUS_ACCEPTED;
            },
            itemIsRejected: function (groupedItem) {
                return groupedItem[0].voting_status === this.VOTING_STATUS_REJECTED;
            },
            itemIsQuorumReached: function (groupedItem) {
                return groupedItem[0].voting_status === this.VOTING_STATUS_QUORUM_REACHED;
            },
            itemIsQuorumFailed: function (groupedItem) {
                return groupedItem[0].voting_status === this.VOTING_STATUS_QUORUM_MISSED;
            },
            quorumCounter: function (groupedVoting) {
                if (groupedVoting[0].quorum_votes === null) {
                    return groupedVoting[0].quorum_custom_current;
                } else {
                    const template = translate.getTranslation("voting", "quorum_counter");
                    return template.replace(/%QUORUM%/, this.voting.quorum).replace(/%CURRENT%/, groupedVoting[0].quorum_votes);
                }
            },
            hasVoteList: function (groupedItem) {
                return groupedItem[0].votes !== undefined;
            },
            isVoteListShown: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                return this.shownVoteLists.indexOf(showId) !== -1;
            },
            showVoteList: function (groupedItem) {
                const showId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists.push(showId);
            },
            hideVoteList: function (groupedItem) {
                const hideId = groupedItem[0].type + '-' + groupedItem[0].id;
                this.shownVoteLists = this.shownVoteLists.filter(id => id !== hideId);
            },
            recalcTimeOffset: function (serverTime) {
                const browserTime = (new Date()).getTime();
                this.timeOffset = browserTime - serverTime.getTime();
            },
            recalcRemainingTime: function () {
                if (this.voting.opened_ts === null) {
                    return;
                }
                const startedTs = (new Date(this.voting.opened_ts)).getTime();
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
