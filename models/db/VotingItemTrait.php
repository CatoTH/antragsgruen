<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\{AntragsgruenApp, JsonConfigTrait, VotingData};
use app\models\exceptions\FormError;

/**
 * @property int|null $votingStatus
 * @property int $votingBlockId
 * @property string|null $votingData
 * @property VotingBlock|null $votingBlock
 */
trait VotingItemTrait
{
    private ?VotingData $votingDataObject = null;

    abstract public function getMyConsultation(): ?Consultation;

    public function getVotingData(): VotingData
    {
        $className = VotingData::class;
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::getVotingDataClass($this->getMyConsultation()) !== null) {
                $className = $plugin::getVotingDataClass($this->getMyConsultation());
            }
        }

        if (!is_object($this->votingDataObject)) {
            /** @var VotingData $object */
            $object = new $className(VotingData::decodeJson5($this->votingData));
            $this->votingDataObject = $object;
        }

        return $this->votingDataObject;
    }

    public function setVotingData(VotingData $data): void
    {
        $this->votingDataObject = $data;
        $this->votingData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @throws FormError
     */
    public function addToVotingBlock(VotingBlock $votingBlock, bool $save): void
    {
        if (!$votingBlock->itemsCanBeAdded()) {
            throw new FormError('Cannot add an item to a running voting');
        }

        $this->votingBlockId = $votingBlock->id;

        foreach ($votingBlock->votes as $vote) {
            if ($vote->isForVotingItem($this)) {
                $vote->delete();
            }
        }

        if ($save) {
            $this->save();
        }
    }

    /**
     * @throws FormError
     */
    public function removeFromVotingBlock(VotingBlock $votingBlock, bool $save): void
    {
        if (!$votingBlock->itemsCanBeRemoved()) {
            throw new FormError('Cannot remove an item from a running voting');
        }

        $this->votingBlockId = null;

        $votingData = $this->getVotingData();
        $votingData->itemGroupSameVote = null;
        $votingData->itemGroupName = null;
        $this->setVotingData($votingData);

        if ($save) {
            $this->save();
        }
    }
}
