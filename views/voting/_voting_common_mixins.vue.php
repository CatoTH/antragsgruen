<?php

use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;
use app\models\db\{IMotion, VotingBlock};
use app\models\policies\UserGroups;

?>
<script>
    const VOTING_COMMON_MIXIN = {
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

                // Voting is closed.
                STATUS_CLOSED: <?= VotingBlock::STATUS_CLOSED ?>,

                QUORUM_TYPE_NONE: <?= IQuorumType::QUORUM_TYPE_NONE ?>,

                VOTES_PUBLIC_NO: <?= VotingBlock::VOTES_PUBLIC_NO ?>,
                VOTES_PUBLIC_ADMIN: <?= VotingBlock::VOTES_PUBLIC_ADMIN ?>,
                VOTES_PUBLIC_ALL: <?= VotingBlock::VOTES_PUBLIC_ALL ?>,

                RESULTS_PUBLIC_YES: <?= VotingBlock::RESULTS_PUBLIC_YES ?>,
                RESULTS_PUBLIC_NO: <?= VotingBlock::RESULTS_PUBLIC_NO ?>,

                ANSWER_TEMPLATE_YES_NO_ABSTENTION: <?= AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ?>,
                ANSWER_TEMPLATE_YES_NO: <?= AnswerTemplates::TEMPLATE_YES_NO ?>,
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
                }, IQuorumType::getQuorumTypes())); ?>
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
                return this.voting.answers_template === this.ANSWER_TEMPLATE_YES_NO_ABSTENTION || this.answers_template === this.ANSWER_TEMPLATE_YES_NO;
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
                return this.voting.status === this.STATUS_CLOSED;
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
            }
        }
    }
</script>
