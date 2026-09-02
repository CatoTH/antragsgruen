<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\api\voting\VotingItemType;
use app\models\settings\VotingData;

interface IVotingItem
{
    public function getId(): int;

    /**
     * What kind of thing is being voted on. The same value as the 'type' entry of
     * getAgendaApiBaseObject(), asked for on its own: reading the type is by far the most frequent
     * question about an item, and building that whole array - proposed procedure, initiators, URLs -
     * in order to answer it is what made a voting payload cost a database query per item.
     */
    public function getVotingItemType(): VotingItemType;

    public function getVotingResult(): ?int;
    public function getMyConsultation(): ?Consultation;
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
