<?php

namespace app\models\proposedProcedure;

use app\components\MotionSorter;
use app\models\api\voting\{VotingBlockAdmin, VotingBlockProposedProcedure, VotingBlockUser, VotingPayloadBuilder};
use app\models\settings\Privileges;
use app\models\db\{IVotingItem, Motion, User, VotingBlock};
use app\models\exceptions\Access;
use app\models\IMotionList;

class AgendaVoting
{
    public IMotionList $itemIds;

    /** @var IVotingItem[] */
    public array $items = [];

    public function __construct(
        public string $title,
        public ?VotingBlock $voting
    ) {
        $this->itemIds = new IMotionList();
    }

    public function addItemsFromBlock(bool $includeNotOnPublicProposalOnes): void
    {
        if (!$this->voting) {
            return;
        }
        foreach ($this->voting->questions as $question) {
            $this->items[]   = $question;
            $this->itemIds->addQuestion($question);
        }

        /** @var Motion[] $motions */
        $motions = MotionSorter::getSortedIMotionsFlat($this->voting->getMyConsultation(), $this->voting->motions);
        foreach ($motions as $motion) {
            if (!$motion->isVisibleForAdmins()) {
                continue;
            }
            if ($motion->getLatestProposal()->isProposalPublic() || $includeNotOnPublicProposalOnes) {
                $this->items[]   = $motion;
                $this->itemIds->addMotion($motion);
            }
        }

        $amendments = MotionSorter::getSortedAmendments($this->voting->getMyConsultation(), $this->voting->amendments);
        foreach ($amendments as $vAmendment) {
            if (!$vAmendment->getMyMotion()) {
                continue;
            }
            if (!$vAmendment->isVisibleForAdmins()) {
                continue;
            }
            if ($vAmendment->getLatestProposal()->isProposalPublic() || $includeNotOnPublicProposalOnes) {
                $this->items[]  = $vAmendment;
                $this->itemIds->addAmendment($vAmendment);
            }
        }
    }

    public static function getFromVotingBlock(VotingBlock $votingBlock): self
    {
        $voting = new AgendaVoting($votingBlock->title, $votingBlock);
        $voting->addItemsFromBlock(true);
        return $voting;
    }

    public function getId(): string
    {
        if ($this->voting) {
            return (string)$this->voting->id;
        } else {
            return 'new';
        }
    }

    public function getProposedProcedureApiObject(bool $hasMultipleVotingBlocks): VotingBlockProposedProcedure
    {
        $title = ($hasMultipleVotingBlocks || $this->voting ? $this->title : null);

        return VotingBlockProposedProcedure::fromAgendaVoting($this, $title);
    }

    public function getAdminApiObject(?User $user): VotingBlockAdmin
    {
        if (!$this->voting->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_VOTINGS, null)) {
            throw new Access('No voting admin permissions');
        }

        return VotingPayloadBuilder::fromAgendaVoting($this)->buildForAdmin($user);
    }

    /**
     * The same payload whether the voting is running or over: what a participant may see follows
     * from the state and the configuration of the voting, not from the page they are looking at.
     */
    public function getUserApiObject(?User $user): VotingBlockUser
    {
        return VotingPayloadBuilder::fromAgendaVoting($this)->buildForUser($user);
    }
}
