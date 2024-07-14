<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion, User};
use app\models\settings\Privileges;

final class IMotionStatusFilter
{
    /** @var int[] */
    private array $disallowedMotionStatuses;
    /** @var int[] */
    private array $disallowedAmendmentStatuses;

    private bool $filterNoAmendmentsIfMotionIsMoved = false;

    private Consultation $consultation;

    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
        $this->disallowedMotionStatuses = $consultation->getStatuses()->getUnreadableStatuses();
        $this->disallowedAmendmentStatuses = $consultation->getStatuses()->getUnreadableStatuses();
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

        $filter->addMotionStatuses($consultation->getStatuses()->getInvisibleMotionStatuses($showWithdrawnAndModified));
        $filter->addAmendmentStatuses($consultation->getStatuses()->getInvisibleAmendmentStatuses($showWithdrawnAndModified));

        return $filter;
    }

    public static function adminExport(Consultation $consultation, bool $inactive): self
    {
        if ($inactive && User::havePrivilege($consultation, Privileges::PRIVILEGE_MOTION_SEE_UNPUBLISHED, null)) {
            $filter = new self($consultation);

            $filter->addMotionStatuses($consultation->getStatuses()->getStatusesInvisibleForAdmins());
            $filter->addAmendmentStatuses($consultation->getStatuses()->getStatusesInvisibleForAdmins());

            return $filter;
        } else {
            return self::onlyUserVisible($consultation, false);
        }
    }

    public function noResolutions(): self
    {
        $this->addMotionStatuses([IMotion::STATUS_RESOLUTION_PRELIMINARY, IMotion::STATUS_RESOLUTION_FINAL]);

        return $this;
    }

    public function noAmendmentsIfMotionIsMoved(): self
    {
        $this->filterNoAmendmentsIfMotionIsMoved = true;

        return $this;
    }

    /**
     * @param IMotion[] $imotions
     *
     * @return Motion[]
     */
    public function filterMotions(array $imotions): array
    {
        $motions = array_filter($imotions, fn(IMotion $imotion) => is_a($imotion, Motion::class));
        return array_values(array_filter($motions, fn(Motion $motion) => !in_array($motion->status, $this->disallowedMotionStatuses)));
    }

    public function getFilteredConsultationMotions(): array
    {
        return $this->filterMotions($this->consultation->motions);
    }

    private function filterAmendment(Amendment $amendment): bool
    {
        if (in_array($amendment->status, $this->disallowedAmendmentStatuses)) {
            return false;
        }
        if ($this->filterNoAmendmentsIfMotionIsMoved && $amendment->getMyMotion()->status === Motion::STATUS_MOVED) {
            return false;
        }
        return true;
    }

    /**
     * @param IMotion[] $imotions
     *
     * @return Amendment[]
     */
    public function filterAmendments(array $imotions): array
    {
        $amendments = array_filter($imotions, fn(IMotion $imotion) => is_a($imotion, Amendment::class));
        return array_values(array_filter($amendments, fn(Amendment $amendment) => $this->filterAmendment($amendment)));
    }

    /**
     * @param Amendment[] $amendments
     *
     * @return Amendment[]
     */
    public function filterAndSortAmendments(array $amendments): array
    {
        $amendments = $this->filterAmendments($amendments);

        return MotionSorter::getSortedAmendments($this->consultation, $amendments);
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

    /**
     * @return IMotion[]
     */
    public function getFilteredConsultationIMotionsSorted(): array
    {
        $motions   = [];
        $motionIds = [];
        $items     = ConsultationAgendaItem::getSortedFromConsultation($this->consultation);

        foreach ($items as $agendaItem) {
            $newMotions = MotionSorter::getSortedIMotionsFlat($this->consultation, $agendaItem->getMyIMotions($this));
            foreach ($newMotions as $newMotion) {
                $motions[]   = $newMotion;
                $motionIds[] = $newMotion->id;
            }
        }
        $noAgendaMotions = [];
        foreach ($this->getFilteredConsultationMotions() as $motion) {
            if ($motion->getMyMotionType()->amendmentsOnly) {
                continue;
            }
            if (!in_array($motion->id, $motionIds)) {
                $noAgendaMotions[] = $motion;
                $motionIds[]       = $motion->id;
            }
        }
        $noAgendaMotions = MotionSorter::getSortedIMotionsFlat($this->consultation, $noAgendaMotions);

        return array_merge($motions, $noAgendaMotions);
    }

    /**
     * @return Motion[]
     */
    public function getFilteredConsultationMotionsSorted(): array
    {
        return array_values(array_filter($this->getFilteredConsultationIMotionsSorted(), fn(IMotion $imotion) => is_a($imotion, Motion::class)));
    }
}
