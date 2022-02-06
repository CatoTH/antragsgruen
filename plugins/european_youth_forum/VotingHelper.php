<?php

namespace app\plugins\european_youth_forum;

use app\models\policies\UserGroups;
use app\models\votings\AnswerTemplates;
use app\models\db\{Consultation, ConsultationUserGroup, User, VotingBlock};

class VotingHelper
{
    public const GROUP_NYC = 'NYC';
    public const GROUP_INGYO = 'INGYO';

    public static function groupIs(ConsultationUserGroup $group, string $groupName): bool
    {
        return strpos($group->title, $groupName) !== false;
    }

    public static function userIsGroup(Consultation $consultation, User $user, string $groupName): bool
    {
        foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
            if (!static::groupIs($userGroup, $groupName)) {
                continue;
            }
            if (in_array($user->id, $userGroup->getUserIds())) {
                return true;
            }
        }

        return false;
    }

    public static function getGroupFromVoting(UserGroups $votingPolicy, string $groupNyme): ?ConsultationUserGroup
    {
        foreach ($votingPolicy->getAllowedUserGroups() as $group) {
            if (self::groupIs($group, $groupNyme)) {
                return $group;
            }
        }
        return null;
    }

    /**
     * A YFJ Voting gets special treatment if:
     * - Policy is set to UserGroups, and both a NYC and INGYO user group is set
     * - Answers are set to Yes/No/Abstention
     */
    public static function isSetUpAsYfjVoting(VotingBlock $votingBlock): bool
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            return false;
        }

        $nyc = self::getGroupFromVoting($policy, self::GROUP_NYC);
        $ingyo = self::getGroupFromVoting($policy, self::GROUP_INGYO);
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
     * @throws InvalidSetupException
     */
    public static function getEligibleUserCountByGroup(VotingBlock $votingBlock, string $groupName): int
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            throw new InvalidSetupException('Policy not set up');
        }
        $group = self::getGroupFromVoting($policy, $groupName);
        if (!$group) {
            throw new InvalidSetupException('Policy not set up');
        }
        return count($group->users);
    }
}
