<?php

namespace app\plugins\european_youth_forum;

use app\models\policies\UserGroups;
use app\models\db\{ConsultationUserGroup, User, VotingBlock};

class VotingHelper
{
    public const GROUP_NYC = 'NYC';
    public const GROUP_INGYO = 'INGYO';

    public static function groupIs(ConsultationUserGroup $group, string $groupName): bool
    {
        return strpos($group->title, $groupName) !== false;
    }

    public static function userIsGroup(User $user, string $groupName): bool
    {
        foreach ($user->userGroups as $userGroup) {
            if (static::groupIs($userGroup, $groupName)) {
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

    public static function isSetUpAsYfjVoting(VotingBlock $votingBlock): bool
    {
        /** @var UserGroups $policy */
        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            return false;
        }
        $nyc = self::getGroupFromVoting($policy, self::GROUP_NYC);
        $ingyo = self::getGroupFromVoting($policy, self::GROUP_INGYO);
        return $nyc && $ingyo;
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
