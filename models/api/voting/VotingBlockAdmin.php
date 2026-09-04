<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingBlockAdmin
{
    public function __construct(
        public int $id,
        public string $title,
        public VotingStatus $status,
        public int $position,
        public int $currentTime,
        /** @var VotingAnswer[] */
        public array $answers,
        public bool $hasMajority,
        public bool $isPresenceCall,
        public VotingPublicity $publicity,
        public VotingStatistics $statistics,
        /** @var VotingItemGroup[] */
        public array $itemGroups,
        /** @var VotingItem[] */
        public array $items,
        public VotingUserState $me,
        public VotingSettings $settings,
        /** @var VotingActivityLogEntry[] */
        public array $log,
        public VotingEditable $editable,
        public ?int $assignedMotionId = null,
        public ?int $openedAt = null,
        public ?int $votingTime = null,
        public ?VotingQuorum $quorum = null,
        public ?VotingAbstention $abstention = null,
        public ?VotingPolicy $policy = null,
        /** @var VotingUserGroup[]|null */
        public ?array $userGroups = null,
        /** @var VotingEligibilityGroup[]|null */
        public ?array $eligibility = null,
    ) {
    }
}
