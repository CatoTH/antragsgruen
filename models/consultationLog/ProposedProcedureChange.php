<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureChange implements \JsonSerializable
{
    use JsonConfigTrait;

    public $proposalStatusFrom = null;
    public $proposalStatusTo = null;

    public $proposalCommentFrom = null;
    public $proposalCommentTo = null;

    public $proposalVotingStatusFrom = null;
    public $proposalVotingStatusTo = null;

    public $proposalExplanationFrom = null;
    public $proposalExplanationTo = null;

    public $votingBlockIdFrom = null;
    public $votingBlockIdTo = null;

    private $hasChanges = false;

    public function setProposalStatusChanges(?int $from, ?int $to): void
    {
        if ($from !== $to) {
            $this->proposalStatusFrom = $from;
            $this->proposalStatusTo = $to;
            $this->hasChanges = true;
        }
    }

    public function setProposalCommentChanges(?string $from, ?string $to): void
    {
        if ($from !== $to) {
            $this->proposalCommentFrom = $from;
            $this->proposalCommentTo = $to;
            $this->hasChanges = true;
        }
    }

    public function setProposalVotingStatusChanges(?int $from, ?int $to): void
    {
        if ($from !== $to) {
            $this->proposalVotingStatusFrom = $from;
            $this->proposalVotingStatusTo = $to;
            $this->hasChanges = true;
        }
    }

    public function setProposalExplanationChanges(?string $from, ?string $to): void
    {
        if ($from !== $to) {
            $this->proposalExplanationFrom = $from;
            $this->proposalExplanationTo = $to;
            $this->hasChanges = true;
        }
    }

    public function setVotingBlockChanges(?int $from, ?int $to): void
    {
        if ($from !== $to) {
            $this->votingBlockIdFrom = $from;
            $this->votingBlockIdTo = $to;
            $this->hasChanges = true;
        }
    }

    public function hasChanges(): bool {
        return $this->hasChanges;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $arr = get_object_vars($this);
        unset($arr['hasChanges']);
        return $arr;
    }
}
