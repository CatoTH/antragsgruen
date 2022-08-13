<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureChange implements \JsonSerializable
{
    use JsonConfigTrait;

    public ?int $proposalStatusFrom = null;
    public ?int $proposalStatusTo = null;

    public ?string $proposalCommentFrom = null;
    public ?string $proposalCommentTo = null;

    public ?int $proposalVotingStatusFrom = null;
    public ?int $proposalVotingStatusTo = null;

    public ?string $proposalExplanationFrom = null;
    public ?string $proposalExplanationTo = null;

    public ?array $proposalTagsFrom = null;
    public ?array $proposalTagsTo = null;

    public ?int $votingBlockIdFrom = null;
    public ?int $votingBlockIdTo = null;

    private bool $hasChanges = false;

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

    public function setProposalTagsHaveChanged(?array $from, ?array $to): void
    {
        $this->proposalTagsFrom = $from;
        $this->proposalTagsTo = $to;
        $this->hasChanges = true;
    }

    public function hasChanges(): bool {
        return $this->hasChanges;
    }

    public function jsonSerialize(): array
    {
        $arr = get_object_vars($this);
        unset($arr['hasChanges']);
        return $arr;
    }
}
