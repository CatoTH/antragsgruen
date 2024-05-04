<?php

namespace app\models\db;

use app\models\settings\VotingData;

/**
 * @property int|null $votingBlockId
 */
interface IVotingItem
{
    public function getAgendaApiBaseObject(): array;
    public function getVotingData(): VotingData;
    public function setVotingData(VotingData $data): void;
    public function setVotingResult(int $votingResult): void;
    public function removeFromVotingBlock(VotingBlock $votingBlock, bool $save): void;
    /** @phpstan-ignore-next-line - method by Yii */
    public function save($runValidation = true, $attributeNames = null);
    public function isGeneralAbstention(): bool;
}
