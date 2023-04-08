<?php

namespace app\plugins\european_youth_forum;

use app\models\policies\UserGroups;
use app\models\votings\AnswerTemplates;
use app\models\db\{Consultation, ConsultationUserGroup, User, VotingBlock};

class VotingHelper
{
    public static function conditionRollCallQuorumRelevant(ConsultationUserGroup $group): bool {
        return mb_stripos($group->title, 'Full member') !== false;
    }

    public static function conditionRollCallIsNycFullMember(ConsultationUserGroup $group): bool {
        // Hint: This matches members WITH voting rights, with NO voting rights, and not participating
        return mb_stripos($group->title, 'Full member') !== false &&
            mb_stripos($group->title, 'NYC') !== false;
    }

    public static function conditionRollCallIsIngyoFullMember(ConsultationUserGroup $group): bool {
        // Hint: This matches members WITH voting rights, with NO voting rights, and not participating
        return mb_stripos($group->title, 'Full member') !== false &&
            mb_stripos($group->title, 'INGYO') !== false;
    }

    public static function conditionShouldBeAssignedToRollCall(ConsultationUserGroup $group): bool {
        if (self::conditionRollCallIsNycFullMember($group) || self::conditionRollCallIsIngyoFullMember($group)) {
            return true;
        }
        if ((mb_stripos($group->title, 'observer') !== false || mb_stripos($group->title, 'candidate') !== false)
            && (mb_stripos($group->title, 'nyc') !== false || mb_stripos($group->title, 'ingyo') !== false)) {
            return true;
        }
        if (mb_stripos($group->title, 'associates') !== false) {
            return true;
        }

        return false;
    }

    public static function conditionVotingIsNycGroup(ConsultationUserGroup $group): bool {
        return mb_stripos($group->title, 'Voting') !== false &&
            mb_stripos($group->title, 'NYC') !== false;
    }

    public static function conditionVotingIsIngyoGroup(ConsultationUserGroup $group): bool {
        return mb_stripos($group->title, 'Voting') !== false &&
            mb_stripos($group->title, 'INGYO') !== false;
    }

    public static function groupMatchesCondition(ConsultationUserGroup $group, callable $condition): bool
    {
        return call_user_func($condition, $group);
    }

    /**
     * @TODO Only consider groups relevant for a specific voting?
     */
    public static function userHasGroupMatchingCondition(Consultation $consultation, User $user, callable $condition): bool
    {
        foreach ($consultation->getAllAvailableUserGroups([], true) as $userGroup) {
            if (!static::groupMatchesCondition($userGroup, $condition)) {
                continue;
            }
            if (in_array($user->id, $userGroup->getUserIds())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ConsultationUserGroup[]
     */
    public static function getGroupsFromVoting(UserGroups $votingPolicy, callable $condition): array
    {
        $groups = [];
        foreach ($votingPolicy->getAllowedUserGroups() as $group) {
            if (self::groupMatchesCondition($group, $condition)) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    public static function getGroupFromVoting(UserGroups $votingPolicy, callable $condition): ?ConsultationUserGroup
    {
        $groups = self::getGroupsFromVoting($votingPolicy, $condition);

        return (count($groups) === 1 ? $groups[0] : null);
    }

    /**
     * A YFJ Voting gets special treatment if:
     * - Policy is set to UserGroups, and both a NYC and INGYO user group is set
     * - Answers are set to Yes/No/Abstention or Yes
     *
     * Keep this in consistent with votings.mixins.vue.php
     */
    public static function isSetUpAsYfjVoting(VotingBlock $votingBlock): bool
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            return false;
        }

        $nyc = self::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsNycGroup']);
        $ingyo = self::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsIngyoGroup']);
        if (!$nyc || !$ingyo) {
            return false;
        }

        $hasYes = false;
        $hasNo = false;
        $hasAbstention = false;
        foreach ($votingBlock->getAnswers() as $answer) {
            if ($answer->dbId === AnswerTemplates::VOTE_YES) {
                $hasYes = true;
            }
            if ($answer->dbId === AnswerTemplates::VOTE_NO) {
                $hasNo = true;
            }
            if ($answer->dbId === AnswerTemplates::VOTE_ABSTENTION) {
                $hasAbstention = true;
            }
        }

        return (
            (count($votingBlock->getAnswers()) === 3 && $hasYes && $hasNo && $hasAbstention) ||
            (count($votingBlock->getAnswers()) === 1 && $hasYes)
        );
    }

    /**
     * Keep this in consistent with votings.mixins.vue.php
     */
    public static function isSetUpAsYfjRollCall(VotingBlock $votingBlock): bool
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            return false;
        }

        $nycMembers = self::getGroupsFromVoting($policy, [VotingHelper::class, 'conditionRollCallIsNycFullMember']);
        $ingyoMembers = self::getGroupsFromVoting($policy, [VotingHelper::class, 'conditionRollCallIsIngyoFullMember']);
        if (!in_array(count($nycMembers), [2, 3]) || !in_array(count($ingyoMembers), [2, 3])) {
            return false;
        }

        return (count($votingBlock->getAnswers()) === 1 && $votingBlock->getAnswers()[0]->dbId === AnswerTemplates::TEMPLATE_PRESENT);
    }

    /**
     * Keep this logic consistent with votings.mixins.vue.php->getRollCallGroupsWithNumbers
     */
    public static function getRollCallResultTable(Consultation $consultation, VotingBlock $votingBlock): array
    {
        $results = [
            "full_ingyo" => [
                "name" => "Full Members INGYO",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool { return self::conditionRollCallIsIngyoFullMember($group); },
            ],
            "full_nyc" => [
                "name" => "Full Members NYC",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool { return self::conditionRollCallIsNycFullMember($group); },
            ],
            "vote_ingyo" => [
                "name" => "Votes INGYO",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'with voting right') !== false && mb_stripos($group->title, 'ingyo') !== false;
                },
            ],
            "vote_nyc" => [
                "name" => "Votes NYC",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'with voting right') !== false && mb_stripos($group->title, 'nyc') !== false;
                },
            ],
            "candidate_ingyo" => [
                "name" => "Candidate members INGYO",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'candidate') !== false && mb_stripos($group->title, 'ingyo') !== false;
                },
            ],
            "candidate_nyc" => [
                "name" => "Candidate members NYC",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'candidate') !== false && mb_stripos($group->title, 'nyc') !== false;
                },
            ],
            "observer_ingyo" => [
                "name" => "Observers INGYO",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'observer') !== false && mb_stripos($group->title, 'ingyo') !== false;
                },
            ],
            "observer_nyc" => [
                "name" => "Observers NYC",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'observer') !== false && mb_stripos($group->title, 'nyc') !== false;
                },
            ],
            "associate" => [
                "name" => "Associates",
                "number" => 0,
                "condition" => function (ConsultationUserGroup $group): bool {
                    return mb_stripos($group->title, 'associate') !== false;
                },
            ]
        ];

        foreach ($votingBlock->votes as $vote) {
            $user = $vote->getUser();
            if (!$user) {
                continue;
            }
            foreach ($results as $resultKey => $result) {
                if (self::userHasGroupMatchingCondition($consultation, $user, $result['condition'])) {
                    $results[$resultKey]['number']++;
                }
            }
        }

        return $results;
    }

    /**
     * @throws InvalidSetupException
     */
    public static function getEligibleUserCountByGroup(VotingBlock $votingBlock, callable $condition): int
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            throw new InvalidSetupException('Policy not set up');
        }
        $group = self::getGroupFromVoting($policy, $condition);
        if (!$group) {
            throw new InvalidSetupException('Policy not set up');
        }
        return count($group->users);
    }
}
