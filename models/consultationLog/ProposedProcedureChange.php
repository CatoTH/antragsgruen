<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureChange implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var null|int */
    public $proposalStatusFrom = null;
    /** @var null|int */
    public $proposalStatusTo = null;

    /** @var null|string */
    public $proposalCommentFrom = null;
    /** @var null|string */
    public $proposalCommentTo = null;

    /** @var null|int */
    public $proposalVotingStatusFrom = null;
    /** @var null|int */
    public $proposalVotingStatusTo = null;

    /** @var null|string */
    public $proposalExplanationFrom = null;
    /** @var null|string */
    public $proposalExplanationTo = null;

    /** @var null|array */
    public $proposalTagsFrom = null;
    /** @var null|array */
    public $proposalTagsTo = null;

    /** @var null|int */
    public $votingBlockIdFrom = null;
    /** @var null|int */
    public $votingBlockIdTo = null;

    /** @var bool */
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
