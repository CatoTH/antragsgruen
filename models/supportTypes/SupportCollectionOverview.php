<?php

declare(strict_types=1);

namespace app\models\supportTypes;

use app\components\{IMotionStatusFilter, MotionSorter};
use app\models\db\{Amendment, Consultation, Motion};

/**
 * All motions and amendments of a consultation that are currently collecting supporters,
 * each sorted by how close they are to reaching the quorum.
 */
class SupportCollectionOverview
{
    /** @var array<array{motion: Motion, progress: SupportCollectionProgress}> */
    public readonly array $motions;

    /** @var array<array{motion: Motion, amendments: array<array{amendment: Amendment, progress: SupportCollectionProgress}>}> */
    public readonly array $amendmentsByMotion;

    public function __construct(Consultation $consultation)
    {
        $motions = [];
        foreach ($consultation->motions as $motion) {
            if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                $supportType = $motion->getMyMotionType()->getMotionSupportTypeClass();
                $motions[] = ['motion' => $motion, 'progress' => SupportCollectionProgress::fromIMotion($motion, $supportType)];
            }
        }
        usort($motions, fn(array $entry1, array $entry2) => SupportCollectionProgress::compare($entry1['progress'], $entry2['progress']));
        $this->motions = $motions;

        $amendmentsByMotion = [];
        $filter = IMotionStatusFilter::onlyUserVisible($consultation, false)->noResolutions();
        foreach ($filter->getFilteredConsultationMotions() as $motion) {
            $amendments = [];
            $supportType = $motion->getMyMotionType()->getAmendmentSupportTypeClass();
            foreach (MotionSorter::getSortedAmendments($consultation, $motion->amendments) as $amendment) {
                if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
                    $amendments[] = ['amendment' => $amendment, 'progress' => SupportCollectionProgress::fromIMotion($amendment, $supportType)];
                }
            }
            if (count($amendments) > 0) {
                usort($amendments, fn(array $entry1, array $entry2) => SupportCollectionProgress::compare($entry1['progress'], $entry2['progress']));
                $amendmentsByMotion[] = ['motion' => $motion, 'amendments' => $amendments];
            }
        }
        $this->amendmentsByMotion = $amendmentsByMotion;
    }

    public function isEmpty(): bool
    {
        return count($this->motions) === 0 && count($this->amendmentsByMotion) === 0;
    }

    public function countMotions(): int
    {
        return count($this->motions);
    }

    public function countMotionsReached(): int
    {
        return count(array_filter($this->motions, fn(array $entry) => $entry['progress']->isReached()));
    }

    public function countAmendments(): int
    {
        return array_sum(array_map(fn(array $group) => count($group['amendments']), $this->amendmentsByMotion));
    }

    public function countAmendmentsReached(): int
    {
        $reached = 0;
        foreach ($this->amendmentsByMotion as $group) {
            $reached += count(array_filter($group['amendments'], fn(array $entry) => $entry['progress']->isReached()));
        }
        return $reached;
    }
}
