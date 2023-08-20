<?php

declare(strict_types=1);

namespace app\plugins\european_youth_forum;

use app\models\votings\AnswerTemplates;
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
            if (in_array($vote->vote, [AnswerTemplates::VOTE_YES, AnswerTemplates::VOTE_PRESENT])) {
                if (in_array($vote->userId, $nycIds)) {
                    $currNyc++;
                }
                if (in_array($vote->userId, $ingyoIds)) {
                    $currIngyo++;
                }
            }
        }

        $quorumNyc = $this->getMinFromGroup($votingBlock, $nyc);
        $quorumIngyo = $this->getMinFromGroup($votingBlock, $ingyo);

        return ($currNyc >=  $quorumNyc && $currIngyo >= $quorumIngyo);
    }

    private function getParticipatingUserInGroup(VotingBlock $votingBlock, ConsultationUserGroup $group): int
    {
        $userIds = $group->getUserIds();
        $votingUserCount = [];
        foreach ($votingBlock->votes as $vote) {
            if ($vote->vote === AnswerTemplates::VOTE_ABSTENTION) {
                continue;
            }
            if (in_array($vote->userId, $userIds) && !in_array($vote->userId, $votingUserCount)) {
                $votingUserCount[] = $vote->userId;
            }
        }
        return count($votingUserCount);
    }

    /**
     * The quorum for each group is a third of all participating delegates.
     * Participating means, a person has voted vor _any_ item in this voting block (except abstentions), not for a specific item.
     * Abstentions are ignored, as if not having voted at all.
     */
    private function getMinFromGroup(VotingBlock $votingBlock, ConsultationUserGroup $group): int
    {
        $votingUserCount = $this->getParticipatingUserInGroup($votingBlock, $group);

        return (int)ceil($votingUserCount / 3);
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

        return 'NYC & INGYO: 1 / 3 of voting delegates';
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
            if (in_array($vote->vote, [AnswerTemplates::VOTE_YES, AnswerTemplates::VOTE_PRESENT])) {
                if (in_array($vote->userId, $nycIds)) {
                    $currNyc++;
                }
                if (in_array($vote->userId, $ingyoIds)) {
                    $currIngyo++;
                }
            }
        }

        return 'INGYO: Ballots cast: ' . $this->getParticipatingUserInGroup($votingBlock, $ingyo) . ', ' .
               'quorum: ' . $currIngyo . ' / ' . $this->getMinFromGroup($votingBlock, $ingyo) . ' --- ' .
               'NYC: Ballots cast: ' . $this->getParticipatingUserInGroup($votingBlock, $nyc) . ', ' .
               'quorum: ' . $currNyc . ' / ' . $this->getMinFromGroup($votingBlock, $nyc);
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
