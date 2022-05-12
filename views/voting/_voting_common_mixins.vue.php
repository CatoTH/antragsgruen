<?php

use app\models\quorumType\IQuorumType;
use app\models\db\{IMotion, VotingBlock};
use app\models\policies\UserGroups;

?>
<script>
    const POLICY_USER_GROUPS = <?= UserGroups::POLICY_USER_GROUPS ?>;

    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = <?= VotingBlock::STATUS_OFFLINE ?>;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = <?= VotingBlock::STATUS_PREPARING ?>;

    // Currently open for voting.
    const STATUS_OPEN = <?= VotingBlock::STATUS_OPEN ?>;

    // Vorting is closed.
    const STATUS_CLOSED = <?= VotingBlock::STATUS_CLOSED ?>;

    const VOTING_STATUS_ACCEPTED = <?= IMotion::STATUS_ACCEPTED ?>;
    const VOTING_STATUS_REJECTED = <?= IMotion::STATUS_REJECTED ?>;
    const VOTING_STATUS_QUORUM_MISSED = <?= IMotion::STATUS_QUORUM_MISSED ?>;
    const VOTING_STATUS_QUORUM_REACHED = <?= IMotion::STATUS_QUORUM_REACHED ?>;

    const QUORUM_TYPE_NONE = <?= IQuorumType::QUORUM_TYPE_NONE ?>;

    const VOTING_COMMON_MIXIN = {
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
                return this.voting.answers_template === ANSWER_TEMPLATE_YES_NO_ABSTENTION || this.answers_template === ANSWER_TEMPLATE_YES_NO;
            },
            votingIsPresenceCall: function () {
                return (this.voting.answers_template === ANSWER_TEMPLATE_PRESENT);
            },
            votingHasQuorum: function () {
                return this.voting.quorum_type !== QUORUM_TYPE_NONE && this.voting.quorum_type !== null;
            },
            isPreparing: function () {
                return this.voting.status === STATUS_PREPARING;
            },
            isOpen: function () {
                return this.voting.status === STATUS_OPEN;
            },
            isClosed: function () {
                return this.voting.status === STATUS_CLOSED;
            }
        },
        methods: {
            itemIsAccepted: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_ACCEPTED;
            },
            itemIsRejected: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_REJECTED;
            },
            itemIsQuorumReached: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_QUORUM_REACHED;
            },
            itemIsQuorumFailed: function (groupedItem) {
                return groupedItem[0].voting_status === VOTING_STATUS_QUORUM_MISSED;
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
