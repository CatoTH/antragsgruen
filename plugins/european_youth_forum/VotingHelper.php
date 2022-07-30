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
        return mb_stripos($group->title, 'Full member') !== false &&
            mb_stripos($group->title, 'NYC') !== false;
    }

    public static function conditionRollCallIsIngyoFullMember(ConsultationUserGroup $group): bool {
        return mb_stripos($group->title, 'Full member') !== false &&
            mb_stripos($group->title, 'INGYO') !== false;
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
     * - Answers are set to Yes/No/Abstention
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

        return $hasYes && $hasNo && $hasAbstention;
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
        if (count($nycMembers) !== 2 || count($ingyoMembers) !== 2) {
            return false;
        }

        return (count($votingBlock->getAnswers()) === 1 && $votingBlock->getAnswers()[0]->dbId === AnswerTemplates::TEMPLATE_PRESENT);
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
