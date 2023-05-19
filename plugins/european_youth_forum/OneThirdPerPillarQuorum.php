<?php

declare(strict_types=1);

namespace app\plugins\european_youth_forum;

use app\models\db\{ConsultationUserGroup, IVotingItem, VotingBlock};
use app\models\policies\UserGroups;
use app\models\quorumType\IQuorumType;

class OneThirdPerPillarQuorum extends IQuorumType
{
    public const QUORUM_TYPE_YFJ_PILLAR_13 = -30;

    public static function getName(): string
    {
        return '1/3 per pillar';
    }

    public static function getDescription(): string
    {
        return 'At least 1/3 of NYC and INGYO need to cast a ballot';
    }

    public static function getID(): int
    {
        return self::QUORUM_TYPE_YFJ_PILLAR_13;
    }

    public function hasReachedQuorum(VotingBlock $votingBlock, IVotingItem $votingItem): bool
    {
        $policy = $votingBlock->getVotingPolicy();
        if (!($policy instanceof UserGroups)) {
            return false;
        }

        $nyc = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsNycGroup']);
        $ingyo = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsIngyoGroup']);
        if (!$nyc || !$ingyo) {
            return false;
        }
        $nycIds = $nyc->getUserIds();
        $ingyoIds = $ingyo->getUserIds();

        $currNyc = 0;
        $currIngyo = 0;
        foreach ($votingBlock->getVotesForVotingItem($votingItem) as $vote) {
            if (in_array($vote->userId, $nycIds)) {
                $currNyc++;
            }
            if (in_array($vote->userId, $ingyoIds)) {
                $currIngyo++;
            }
        }

        return ($currNyc >= $this->getMinFromGroup($nyc) && $currIngyo >= $this->getMinFromGroup($ingyo));
    }

    private function getMinFromGroup(ConsultationUserGroup $group): int
    {
        return (int)ceil(count($group->getUserIds()) / 3);
    }

    public function getCustomQuorumTarget(VotingBlock $votingBlock): ?string
    {
        $policy = $votingBlock->getVotingPolicy();
        if (!($policy instanceof UserGroups)) {
            return 'Not set up correctly (needs to be UserGroups policy)';
        }

        $nyc = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsNycGroup']);
        $ingyo = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsIngyoGroup']);
        if (!$nyc || !$ingyo) {
            return 'Did not find NYC/INGYO user groups';
        }

        return 'NYC: ' . $this->getMinFromGroup($nyc) . ', INGYO: ' . $this->getMinFromGroup($ingyo);
    }

    public function getCustomQuorumCurrent(VotingBlock $votingBlock, IVotingItem $votingItem): ?string
    {
        $policy = $votingBlock->getVotingPolicy();
        if (!($policy instanceof UserGroups)) {
            return 'Not set up correctly (needs to be UserGroups policy)';
        }

        $nyc = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsNycGroup']);
        $ingyo = VotingHelper::getGroupFromVoting($policy, [VotingHelper::class, 'conditionVotingIsIngyoGroup']);
        if (!$nyc || !$ingyo) {
            return 'Did not find NYC/INGYO user groups';
        }
        $nycIds = $nyc->getUserIds();
        $ingyoIds = $ingyo->getUserIds();

        $currNyc = 0;
        $currIngyo = 0;
        foreach ($votingBlock->getVotesForVotingItem($votingItem) as $vote) {
            if (in_array($vote->userId, $nycIds)) {
                $currNyc++;
            }
            if (in_array($vote->userId, $ingyoIds)) {
                $currIngyo++;
            }
        }

        return 'Quorum: NYC: ' . $currNyc . ' / ' . $this->getMinFromGroup($nyc) . ', ' .
            'INGYO: ' . $currIngyo . ' / ' . $this->getMinFromGroup($ingyo);
    }

    public function getRelevantVotedCount(VotingBlock $votingBlock, IVotingItem $votingItem): ?int
    {
        return null;
    }

    public function getQuorum(VotingBlock $votingBlock): ?int
    {
        return null;
    }
}
