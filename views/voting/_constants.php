<?php

use app\models\api\voting\VotingStatus;
use app\models\majorityType\IMajorityType;
use app\models\policies\{IPolicy, UserGroups};
use app\models\quorumType\IQuorumType;
use app\models\votings\AnswerTemplates;

// The values a widget compares payload fields against. Everything that is part of the payload itself
// is described in docs/openapi.yaml; what is left here are the values the settings form writes back
// and the lists it offers.

return [
    "POLICY_USER_GROUPS" => UserGroups::POLICY_USER_GROUPS,

    // The status of a voting, as the payload spells it (VotingStatus)

    // The voting is not performed using Antragsgrün
    "STATUS_OFFLINE" => VotingStatus::OFFLINE->value,

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    "STATUS_PREPARING" => VotingStatus::PREPARING->value,

    // Currently open for voting.
    "STATUS_OPEN" => VotingStatus::OPEN->value,

    // Voting is closed, results are visible for users.
    "STATUS_CLOSED_PUBLISHED" => VotingStatus::CLOSED_PUBLISHED->value,

    // Voting is closed, results are not visible for users.
    "STATUS_CLOSED_UNPUBLISHED" => VotingStatus::CLOSED_UNPUBLISHED->value,

    "QUORUM_TYPE_NONE" => IQuorumType::QUORUM_TYPE_NONE,

    "ANSWER_TEMPLATE_YES_NO_ABSTENTION" => AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION,
    "ANSWER_TEMPLATE_YES_NO" => AnswerTemplates::TEMPLATE_YES_NO,
    "ANSWER_TEMPLATE_YES" => AnswerTemplates::TEMPLATE_YES,
    "ANSWER_TEMPLATE_PRESENT" => AnswerTemplates::TEMPLATE_PRESENT,

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
