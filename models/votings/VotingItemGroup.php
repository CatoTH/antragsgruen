<?php

declare(strict_types=1);

namespace app\models\votings;

use app\models\db\{Amendment, IMotion, Motion, VotingBlock};
use app\models\exceptions\FormError;

class VotingItemGroup
{
    private const ADHOC_PREFIX = 'adhoc-';
    private const ADHOC_PREFIX_MOTION = 'motion-';
    private const ADHOC_PREFIX_AMENDMENT = 'amendment-';

    public string $groupId;
    public ?string $groupName;

    /** @var int[] */
    public array $motionIds = [];

    /** @var int[] */
    public array $amendmentIds = [];

    /** @var Motion[] */
    public array $motions = [];

    /** @var Amendment[] */
    public array $amendments = [];


    /**
     * Hint: either $groupId or $adhocItem needs to be provided
     */
    public function __construct(?string $groupId, ?string $groupName, ?IMotion $adhocItem)
    {
        $this->groupName = $groupName;

        if ($groupId === null && is_a($adhocItem, Motion::class)) {
            $this->groupId = self::ADHOC_PREFIX . self::ADHOC_PREFIX_MOTION . $adhocItem->id;
            $this->motions[] = $adhocItem;
            $this->motionIds[] = $adhocItem->id;
        } elseif ($groupId === null && is_a($adhocItem, Amendment::class)) {
            $this->groupId = self::ADHOC_PREFIX . self::ADHOC_PREFIX_AMENDMENT . $adhocItem->id;
            $this->amendments[] = $adhocItem;
            $this->amendmentIds[] = $adhocItem->id;
        } else {
            $this->groupId = $groupId;
        }
    }

    public function isAdhocGroup(): bool
    {
        return (str_starts_with($this->groupId, self::ADHOC_PREFIX));
    }

    public function isOnlyMyselfGroup(IMotion $item): bool {
        if (is_a($item, Motion::class) && count($this->motionIds) === 1 && count($this->amendmentIds) === 0 && $this->motionIds[0] === $item->id) {
            return true;
        }
        if (is_a($item, Amendment::class) && count($this->amendmentIds) === 1 && count($this->motionIds) === 0 && $this->amendmentIds[0] === $item->id) {
            return true;
        }
        return false;
    }

    public function getTitle(?IMotion $excludeFromTitle = null): string
    {
        $titles = [];
        foreach ($this->motions as $motion) {
            if ($excludeFromTitle && is_a($excludeFromTitle, Motion::class) && $motion->id === $excludeFromTitle->id) {
                continue;
            }
            $titles[] = $motion->getFormattedTitlePrefix() ?: $motion->getTitleWithPrefix();
        }
        foreach ($this->amendments as $amendment) {
            if ($excludeFromTitle && is_a($excludeFromTitle, Amendment::class) && $amendment->id === $excludeFromTitle->id) {
                continue;
            }
            $titles[] = $amendment->getFormattedTitlePrefix() ?: $amendment->getTitleWithPrefix();
        }
        return implode(', ', $titles);
    }

    public static function setVotingItemGroupToAllItems(VotingBlock $votingBlock, IMotion $imotion, string $idToSet, ?string $nameToSet): void
    {
        if (str_starts_with($idToSet, self::ADHOC_PREFIX)) {
            $otherItem = null;
            if (str_contains($idToSet, self::ADHOC_PREFIX_AMENDMENT)  ) {
                $groupWithId = intval(explode('-', $idToSet)[2]);
                $otherItem = $imotion->getMyConsultation()->getAmendment($groupWithId);
            }
            if (str_contains($idToSet, self::ADHOC_PREFIX_MOTION)  ) {
                $groupWithId = intval(explode('-', $idToSet)[2]);
                $otherItem = $imotion->getMyConsultation()->getMotion($groupWithId);
            }
            if (!$otherItem) {
                throw new FormError('inalid id provided: ' . $idToSet);
            }
            /** @var IMotion $otherItem */

            $newGroupId = uniqid();

            $settings = $otherItem->getVotingData();
            $settings->itemGroupSameVote = $newGroupId;
            $settings->itemGroupName = $nameToSet;
            $otherItem->setVotingData($settings);
            $otherItem->save();

            $settings = $imotion->getVotingData();
            $settings->itemGroupSameVote = $newGroupId;
            $settings->itemGroupName = $nameToSet;
            $imotion->setVotingData($settings); // Don't save yet
        } else {
            foreach ($votingBlock->getItemGroupItems($idToSet) as $otherItem) {
                $settings = $otherItem->getVotingData();
                $settings->itemGroupName = $nameToSet;
                $otherItem->setVotingData($settings);
                $otherItem->save();
            }

            $settings = $imotion->getVotingData();
            $settings->itemGroupName = $nameToSet;
            $settings->itemGroupSameVote = $idToSet;
            $imotion->setVotingData($settings);
        }
    }
}
