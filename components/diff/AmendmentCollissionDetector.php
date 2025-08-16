<?php

declare(strict_types=1);

namespace app\components\diff;

use app\models\db\{Amendment, Motion};
use app\models\sectionTypes\ISectionType;

class AmendmentCollissionDetector
{
    /**
     * @param null|int[] $excludeAmendmentIds
     * @return Amendment[]
     */
    public static function getHeuristicallyRelevantAmendments(Motion $motion, ?array $excludeAmendmentIds = null): array
    {
        $amendments = [];
        foreach ($motion->amendments as $amendment) {
            if ($excludeAmendmentIds && in_array($amendment->id, $excludeAmendmentIds)) {
                continue;
            }
            if ($amendment->markForMergingByDefault(true)) {
                $amendments[] = $amendment;
                if ($amendment->getLatestProposal()->hasAlternativeProposaltext(false)) {
                    /** @var Amendment $proposedChange */
                    $proposedChange = $amendment->getLatestProposal()->getMyProposalReference();
                    $amendments[] = $proposedChange;
                }
            }
        }

        return $amendments;
    }

    /**
     * @param Amendment[] $checkAgainstAmendments
     * @param array<int, mixed> $newSections
     *
     * @return Amendment[]
     */
    public static function getAmendmentsCollidingWithSections(array $checkAgainstAmendments, array $newSections): array
    {
        $collidesWith = [];

        foreach ($checkAgainstAmendments as $amendment) {
            if ($amendment->globalAlternative) {
                $collidesWith[] = $amendment;
                continue;
            }
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $collidesWith, true)) {
                        $collidesWith[] = $amendment;
                    }
                }
            }
        }

        return $collidesWith;
    }
}
