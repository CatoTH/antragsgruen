<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\VotingData;

interface IVotingItem
{
    public function getAgendaApiBaseObject(): array;
    public function getVotingBlockId(): ?int;
    public function getVotingData(): VotingData;
    public function setVotingData(VotingData $data): void;
    public function setVotingResult(int $votingResult): void;
    public function removeFromVotingBlock(VotingBlock $votingBlock, bool $save): void;
    /** @phpstan-ignore-next-line - method by Yii */
    public function save($runValidation = true, $attributeNames = null);
    public function isGeneralAbstention(): bool;
}
