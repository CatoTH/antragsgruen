<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\proposedProcedure\AgendaVoting;

class VotingBlockProposedProcedure
{
    public function __construct(
        /** @var VotingAnswer[] */
        public array $answers,
        public bool $hasMajority,
        public bool $isPresenceCall,
        /** @var VotingItemGroup[] */
        public array $itemGroups,
        /** @var VotingItem[] */
        public array $items,
        public ?int $id = null,
        public ?string $title = null,
        public ?VotingStatus $status = null,
        public ?int $assignedMotionId = null,
        public ?VotingQuorum $quorum = null,
        public ?VotingStatistics $statistics = null,
        public ?VotingPolicy $policy = null,
        /** @var VotingUserGroup[]|null */
        public ?array $userGroups = null,
    ) {
    }

    /**
     * The proposed procedure also lists items that are meant to be voted on but have no voting yet
     * ("unhandled"), which is why everything describing a voting is optional here.
     */
    public static function fromAgendaVoting(AgendaVoting $agendaVoting, ?string $title): self
    {
        if ($agendaVoting->voting === null) {
            return new self(
                answers: [],
                hasMajority: false,
                isPresenceCall: false,
                itemGroups: VotingPayloadBuilder::buildItemGroupsWithoutVotes($agendaVoting->items),
                items: VotingPayloadBuilder::buildItems($agendaVoting->items),
                title: $title,
            );
        }

        return VotingPayloadBuilder::fromAgendaVoting($agendaVoting)->buildForProposedProcedure($title);
    }
}
