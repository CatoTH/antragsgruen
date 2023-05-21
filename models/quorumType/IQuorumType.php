<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\db\{IVotingItem, User, VotingBlock};
use app\models\exceptions\Internal;
use app\models\policies\UserGroups;
use app\models\settings\AntragsgruenApp;

abstract class IQuorumType
{
    // No quorum
    public const QUORUM_TYPE_NONE = 0;

    // At least half of eligible members are present
    public const QUORUM_TYPE_HALF = 1;

    // At least 2/3 of all members are present
    public const QUORUM_TYPE_TWO_THIRD = 3;

    /**
     * @return string[]|IQuorumType[]
     */
    public static function getQuorumTypes(): array
    {
        $quorumTypes = [
            self::QUORUM_TYPE_NONE => NoQuorum::class,
            self::QUORUM_TYPE_HALF => HalfQuorum::class,
            self::QUORUM_TYPE_TWO_THIRD => TwoThirdsQuorum::class,
        ];
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            foreach ($plugin::getAdditionalQuorumTypes() as $key => $val) {
                $quorumTypes[$key] = $val;
            }
        }
        return $quorumTypes;
    }

    abstract public function getQuorum(VotingBlock $votingBlock): ?int;

    /**
     * To be overwritten by custom quorum types. Note that this will only be shown if getQuorum() returns null.
     */
    public function getCustomQuorumTarget(VotingBlock $votingBlock): ?string
    {
        return null;
    }

    public function hasReachedQuorum(VotingBlock $votingBlock, IVotingItem $votingItem): bool
    {
        $quorum = $this->getQuorum($votingBlock);

        return $this->getRelevantVotedCount($votingBlock, $votingItem) >= $quorum;
    }

    private function userIsRelevantForQuorum(VotingBlock $votingBlock, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Any plugin-provided rule has precedence
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $relevant = $plugin::userIsRelevantForQuorum($votingBlock, $user);
            if ($relevant !== null) {
                return $relevant;
            }
        }

        // By default, every user is relevant for the quorum
        return true;
    }

    public function getRelevantEligibleVotersCount(VotingBlock $votingBlock): int
    {
        // Any plugin-provided rule has precedence
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $userCount = $plugin::getRelevantEligibleVotersCount($votingBlock);
            if ($userCount !== null) {
                return $userCount;
            }
        }

        $policy = $votingBlock->getVotingPolicy();
        if (!is_a($policy, UserGroups::class)) {
            return 0;
        }

        $userIds = [];
        foreach ($policy->getAllowedUserGroups() as $userGroup) {
            foreach ($userGroup->getUsersCached() as $user) {
                if (!in_array($user->id, $userIds) && $this->userIsRelevantForQuorum($votingBlock, $user)) {
                    $userIds[] = $user->id;
                }
            }
        }

        return count($userIds);
    }

    public function getRelevantVotedCount(VotingBlock $votingBlock, IVotingItem $votingItem): ?int
    {
        $votes = $votingBlock->getVotesForVotingItem($votingItem);
        $count = 0;
        foreach ($votes as $vote) {
            if ($vote->userId !== null && $this->userIsRelevantForQuorum($votingBlock, $vote->getUser())) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * To be overwritten by custom quorum types. Note that this will only be shown if getRelevantVotedCount() returns null.
     */
    public function getCustomQuorumCurrent(VotingBlock $votingBlock, IVotingItem $votingItem): ?string
    {
        return null;
    }

    public static function getID(): int
    {
        throw new Internal('Cannot be called on the abstract base method');
    }

    public static function getDescription(): string
    {
        throw new Internal('Cannot be called on the abstract base method');
    }

    public static function getName(): string
    {
        throw new Internal('Cannot be called on the abstract base method');
    }
}
