<?php

use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;
use app\models\db\{IMotion, VotingBlock};
use app\models\policies\UserGroups;

?>
<script>
    const quorumCounter = <?= json_encode(Yii::t('voting', 'quorum_counter')) ?>;

    if (window.VOTING_COMMON_MIXINS === undefined) {
        window.VOTING_COMMON_MIXINS = [];
    }
    window.VOTING_COMMON_MIXINS.push({
        data() {
            return {
                // Keep in sync with VotingBlock.php

                VOTING_STATUS_ACCEPTED: <?= IMotion::STATUS_ACCEPTED ?>,
                VOTING_STATUS_REJECTED: <?= IMotion::STATUS_REJECTED ?>,
                VOTING_STATUS_QUORUM_MISSED: <?= IMotion::STATUS_QUORUM_MISSED ?>,
                VOTING_STATUS_QUORUM_REACHED: <?= IMotion::STATUS_QUORUM_REACHED ?>,

                POLICY_USER_GROUPS: <?= UserGroups::POLICY_USER_GROUPS ?>,

                // The voting is not performed using Antragsgrün
                STATUS_OFFLINE: <?= VotingBlock::STATUS_OFFLINE ?>,

                // Votings that have been created and will be using Antragsgrün, but are not active yet
                STATUS_PREPARING: <?= VotingBlock::STATUS_PREPARING ?>,

                // Currently open for voting.
                STATUS_OPEN: <?= VotingBlock::STATUS_OPEN ?>,

                // Voting is closed, results are visible for users.
                STATUS_CLOSED_PUBLISHED: <?= VotingBlock::STATUS_CLOSED_PUBLISHED ?>,

                // Voting is closed, results are not visible for users.
                STATUS_CLOSED_UNPUBLISHED: <?= VotingBlock::STATUS_CLOSED_UNPUBLISHED ?>,

                QUORUM_TYPE_NONE: <?= IQuorumType::QUORUM_TYPE_NONE ?>,

                VOTES_PUBLIC_NO: <?= VotingBlock::VOTES_PUBLIC_NO ?>,
                VOTES_PUBLIC_ADMIN: <?= VotingBlock::VOTES_PUBLIC_ADMIN ?>,
                VOTES_PUBLIC_ALL: <?= VotingBlock::VOTES_PUBLIC_ALL ?>,

                RESULTS_PUBLIC_YES: <?= VotingBlock::RESULTS_PUBLIC_YES ?>,
                RESULTS_PUBLIC_NO: <?= VotingBlock::RESULTS_PUBLIC_NO ?>,

                ANSWER_TEMPLATE_YES_NO_ABSTENTION: <?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>,
                ANSWER_TEMPLATE_YES_NO: <?= AnswerTemplates::TEMPLATE_YES_NO ?>,
                ANSWER_TEMPLATE_YES: <?= AnswerTemplates::TEMPLATE_YES ?>,
                ANSWER_TEMPLATE_PRESENT: <?= AnswerTemplates::TEMPLATE_PRESENT ?>,

                ACTIVITY_TYPE_OPENED: <?= VotingBlock::ACTIVITY_TYPE_OPENED ?>,
                ACTIVITY_TYPE_CLOSED: <?= VotingBlock::ACTIVITY_TYPE_CLOSED ?>,
                ACTIVITY_TYPE_RESET: <?= VotingBlock::ACTIVITY_TYPE_RESET ?>,
                ACTIVITY_TYPE_REOPENED: <?= VotingBlock::ACTIVITY_TYPE_REOPENED ?>,

                VOTE_POLICY_USERGROUPS: <?= IPolicy::POLICY_USER_GROUPS ?>,

                MAJORITY_TYPES: <?= json_encode(array_map(function ($className) {
                    return [
                        'id' => $className::getID(),
                        'name' => $className::getName(),
                        'description' => $className::getDescription(),
                    ];
                }, IMajorityType::getMajorityTypes())); ?>,

                QUORUM_TYPES: <?= json_encode(array_map(function ($className) {
                    return [
                        'id' => $className::getID(),
                        'name' => $className::getName(),
                        'description' => $className::getDescription(),
                    ];
                }, IQuorumType::getQuorumTypes())); ?>,

                timerId: null,
                timeOffset: 0, // milliseconds the browser is ahead of the server time
                remainingVotingTime: null
            }
        },
        watch: {
            voting: {
                handler(newVal) {
                    this.recalcTimeOffset(new Date(newVal.current_time));
                    this.recalcRemainingTime();
                },
                immediate: true
            }
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
                    return quorumCounter.replace(/%QUORUM%/, this.voting.quorum).replace(/%CURRENT%/, groupedVoting[0].quorum_votes);
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
    });
</script>
