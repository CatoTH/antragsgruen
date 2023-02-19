<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\Motion;

class MotionHistory
{
    public static function getHistoryRootMotion(Motion $motion, array $alreadySeenIds = []): Motion
    {
        if ($motion->replacedMotion && !in_array($motion->id, $alreadySeenIds, true)) {
            $alreadySeenIds[] = $motion->id;
            return self::getHistoryRootMotion($motion->replacedMotion, $alreadySeenIds);
        } else {
            return $motion;
        }
    }

    /**
     * @return Motion[]
     */
    public static function getHistoryFromRoot(Motion $motion, array $alreadySeenIds = []): array
    {
        $motions = [$motion];
        foreach ($motion->replacedByMotions as $replacedByMotion) {
            if (in_array($replacedByMotion->id, $alreadySeenIds, true)) {
                continue;
            }
            $alreadySeenIds[] = $replacedByMotion->id;
            $submotions = self::getHistoryFromRoot($replacedByMotion, $alreadySeenIds);
            $motions = array_merge($motions, $submotions);
        }

        return $motions;
    }

    /**
     * @return Motion[]
     */
    public static function getSortedHistoryForMotion(Motion $motion, bool $onlyVisible): array
    {
        $root = self::getHistoryRootMotion($motion);
        $history = self::getHistoryFromRoot($root);

        if ($onlyVisible) {
            $invisibleStatuses = $motion->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses();
        } else {
            $invisibleStatuses = $motion->getMyConsultation()->getStatuses()->getUnreadableStatuses();
        }
        $history = array_values(array_filter($history, function (Motion $motion) use ($invisibleStatuses): bool {
            return !in_array($motion->status, $invisibleStatuses);
        }));

        usort($history, function (Motion $motion1, Motion $motion2): int {
            if ($motion1->version < $motion2->version) {
                return -1;
            }
            if ($motion2->version < $motion1->version) {
                return 1;
            }
            return $motion1->getTimestamp() <=> $motion2->getTimestamp();
        });

        return $history;
    }
}
