<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, IMotion, Motion};
use app\models\settings\IMotionStatusEngine;

final class IMotionStatusFilter
{
    /** @var int[] */
    private array $disallowedMotionStatuses;
    /** @var int[] */
    private array $disallowedAmendmentStatuses;

    private IMotionStatusEngine $statuses;

    public function __construct(Consultation $consultation)
    {
        $this->statuses = $consultation->getStatuses();
        $this->disallowedMotionStatuses = $this->statuses->getUnreadableStatuses();
        $this->disallowedAmendmentStatuses = $this->statuses->getUnreadableStatuses();
    }

    /**
     * @param int[] $statuses
     */
    private function addMotionStatuses(array $statuses): void
    {
        $this->disallowedMotionStatuses = array_values(array_unique(array_merge(
            $this->disallowedMotionStatuses,
            $statuses
        )));
    }

    /**
     * @param int[] $statuses
     */
    private function addAmendmentStatuses(array $statuses): void
    {
        $this->disallowedAmendmentStatuses = array_values(array_unique(array_merge(
            $this->disallowedAmendmentStatuses,
            $statuses
        )));
    }

    public static function onlyUserVisible(Consultation $consultation, bool $showWithdrawnAndModified): self
    {
        $filter = new self($consultation);

        $filter->addMotionStatuses($filter->statuses->getInvisibleMotionStatuses($showWithdrawnAndModified));
        $filter->addAmendmentStatuses($filter->statuses->getInvisibleAmendmentStatuses($showWithdrawnAndModified));

        return $filter;
    }

    public function noResolutions(): self
    {
        $this->addMotionStatuses([IMotion::STATUS_RESOLUTION_PRELIMINARY, IMotion::STATUS_RESOLUTION_FINAL]);

        return $this;
    }

    /**
     * @param Motion[] $motions
     *
     * @return Motion[]
     */
    public function filterMotions(array $motions): array
    {
        return array_values(array_filter($motions, fn(Motion $motion) => !in_array($motion->status, $this->disallowedMotionStatuses)));
    }

    /**
     * @param Amendment[] $amendments
     *
     * @return Amendment[]
     */
    public function filterAmendments(array $amendments): array
    {
        return array_values(array_filter($amendments, fn(Amendment $amendments) => !in_array($amendments->status, $this->disallowedAmendmentStatuses)));
    }

    /**
     * @param IMotion[] $imotions
     *
     * @return IMotion[]
     */
    public function filterIMotions(array $imotions): array
    {
        return array_values(array_filter($imotions, function(IMotion $imotion) {
            if (is_a($imotion, Motion::class)) {
                return !in_array($imotion->status, $this->disallowedMotionStatuses);
            } else {
                /** @var Amendment $imotion */
                return !in_array($imotion->status, $this->disallowedAmendmentStatuses);
            }
        }));
    }
}
