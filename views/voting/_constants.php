<?php

use app\models\db\{IMotion, VotingBlock};
use app\models\majorityType\IMajorityType;
use app\models\policies\{IPolicy, UserGroups};
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;

return [
    // Keep in sync with VotingBlock.php

    "VOTING_STATUS_ACCEPTED" => IMotion::STATUS_ACCEPTED,
    "VOTING_STATUS_REJECTED" => IMotion::STATUS_REJECTED,
    "VOTING_STATUS_QUORUM_MISSED" => IMotion::STATUS_QUORUM_MISSED,
    "VOTING_STATUS_QUORUM_REACHED" => IMotion::STATUS_QUORUM_REACHED,

    "POLICY_USER_GROUPS" => UserGroups::POLICY_USER_GROUPS,

    // The voting is not performed using Antragsgrün
    "STATUS_OFFLINE" => VotingBlock::STATUS_OFFLINE,

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    "STATUS_PREPARING" => VotingBlock::STATUS_PREPARING,

    // Currently open for voting.
    "STATUS_OPEN" => VotingBlock::STATUS_OPEN,

    // Voting is closed, results are visible for users.
    "STATUS_CLOSED_PUBLISHED" => VotingBlock::STATUS_CLOSED_PUBLISHED,

    // Voting is closed, results are not visible for users.
    "STATUS_CLOSED_UNPUBLISHED" => VotingBlock::STATUS_CLOSED_UNPUBLISHED,

    "QUORUM_TYPE_NONE" => IQuorumType::QUORUM_TYPE_NONE,

    "VOTES_PUBLIC_NO" => VotingBlock::VOTES_PUBLIC_NO,
    "VOTES_PUBLIC_ADMIN" => VotingBlock::VOTES_PUBLIC_ADMIN,
    "VOTES_PUBLIC_ALL" => VotingBlock::VOTES_PUBLIC_ALL,

    "RESULTS_PUBLIC_YES" => VotingBlock::RESULTS_PUBLIC_YES,
    "RESULTS_PUBLIC_NO" => VotingBlock::RESULTS_PUBLIC_NO,

    "ANSWER_TEMPLATE_YES_NO_ABSTENTION" => AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION,
    "ANSWER_TEMPLATE_YES_NO" => AnswerTemplates::TEMPLATE_YES_NO,
    "ANSWER_TEMPLATE_YES" => AnswerTemplates::TEMPLATE_YES,
    "ANSWER_TEMPLATE_PRESENT" => AnswerTemplates::TEMPLATE_PRESENT,

    "ACTIVITY_TYPE_OPENED" => VotingBlock::ACTIVITY_TYPE_OPENED,
    "ACTIVITY_TYPE_CLOSED" => VotingBlock::ACTIVITY_TYPE_CLOSED,
    "ACTIVITY_TYPE_RESET" => VotingBlock::ACTIVITY_TYPE_RESET,
    "ACTIVITY_TYPE_REOPENED" => VotingBlock::ACTIVITY_TYPE_REOPENED,

    "VOTE_POLICY_USERGROUPS" => IPolicy::POLICY_USER_GROUPS,

    "MAJORITY_TYPES" => array_map(function ($className) {
        return [
            'id' => $className::getID(),
            'name' => $className::getName(),
            'description' => $className::getDescription(),
        ];
    }, IMajorityType::getMajorityTypes()),

    "QUORUM_TYPES" => array_map(function ($className) {
        return [
            'id' => $className::getID(),
            'name' => $className::getName(),
            'description' => $className::getDescription(),
        ];
    }, IQuorumType::getQuorumTypes()),
];
