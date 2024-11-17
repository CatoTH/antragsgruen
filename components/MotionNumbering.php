<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\repostory\MotionRepository;

class MotionNumbering
{
    public static function getNewTitlePrefixInternal(string $titlePrefix): string
    {
        $new = \Yii::t('motion', 'prefix_new_code');
        $newMatch = preg_quote($new, '/');
        if (preg_match('/' . $newMatch . '/i', $titlePrefix)) {
            $parts = preg_split('/(' . $newMatch . '\s*)/i', $titlePrefix, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts === false) {
                return $titlePrefix . $new;
            }
            $last = (int)array_pop($parts);
            $last = ($last > 0 ? $last + 1 : 2); // NEW BLA -> NEW 2
            $parts[] = $last;

            return implode("", $parts);
        } else {
            return $titlePrefix . $new;
        }
    }

    public static function getNewVersion(string $version): string
    {
        if (preg_match("/^(?<pre>.*?)(?<version>\d+)$/siu", $version, $matches)) {
            $newVersion = (int)$matches['version'] + 1;
            return $matches['pre'] . $newVersion;
        } else {
            return $version . '2';
        }
    }

    /**
     * @return Motion[]
     */
    public static function getHistoryRootMotion(Motion $motion, bool $includeObsoletedByMotions, array $alreadySeenIds = []): array
    {
        $roots = [];

        $replacedMotion = MotionRepository::getReplacedByMotion($motion);
        if ($replacedMotion) {
            if (!in_array($replacedMotion->id, $alreadySeenIds)) {
                $alreadySeenIds[] = $replacedMotion->id;
                $roots = array_merge($roots, self::getHistoryRootMotion($replacedMotion, $includeObsoletedByMotions, $alreadySeenIds));
            }
        } else {
            // Hint: this motion is considered a root motion even if below there is another motion found that is obsoleted by this motion
            $roots[] = $motion;
        }

        // Add the root motions of those motions that have been obsoleted by the current motion
        foreach (MotionRepository::getObsoletedByMotionsInAllConsultations($motion) as $obsoletedMotion) {
            if (!in_array($obsoletedMotion->id, $alreadySeenIds)) {
                $alreadySeenIds[] = $obsoletedMotion->id;
                $roots = array_merge($roots, self::getHistoryRootMotion($obsoletedMotion, $includeObsoletedByMotions, $alreadySeenIds));
            }
        }

        return $roots;
    }

    /**
     * @return Motion[]
     */
    public static function getHistoryFromRoot(Motion $motion, array $alreadySeenIds = []): array
    {
        $motions = [$motion];
        foreach (MotionRepository::getReplacedByMotionsInAllConsultations($motion) as $replacedByMotion) {
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
     * @param Motion[] $rootMotions
     *
     * @return Motion[]
     */
    public static function getHistoryFromRoots(array $rootMotions): array
    {
        $alreadySeenIds = [];
        $motions = [];
        foreach ($rootMotions as $rootMotion) {
            $alreadySeenIds[] = $rootMotion->id;
            $motions = array_merge($motions, self::getHistoryFromRoot($rootMotion, $alreadySeenIds));
        }

        return $motions;
    }

    /**
     * @return Motion[]
     */
    public static function getSortedHistoryForMotion(Motion $motion, bool $onlyVisible, bool $includeObsoletedByMotions = false): array
    {
        $roots = self::getHistoryRootMotion($motion, $includeObsoletedByMotions, []);
        $history = self::getHistoryFromRoots($roots);

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

    public static function findMotionInHistoryOfVersion(Motion $motion, string $version): ?Motion
    {
        foreach (self::getSortedHistoryForMotion($motion, false) as $motion) {
            if ($motion->version === $version) {
                return $motion;
            }
        }
        return null;
    }

    public static function findMostRecentVersionOfMotion(Motion $motion, bool $userVisibleOnly): ?Motion
    {
        $invisibleStatuses = $motion->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses();
        if ($userVisibleOnly) {
            $invisibleStatuses = array_merge($invisibleStatuses, $motion->getMyConsultation()->getStatuses()->getUnreadableStatuses());
        }

        // Note: this searches for the motion deepest down in the tree.
        // It assumes that only one motion is set to replace the current one; if multiple motions are replacing it,
        // the behavior is somewhat undefined for now
        $directDescendant = null;
        $subDescendant = null;
        foreach (MotionRepository::getReplacedByMotionsInAllConsultations($motion) as $replacedByMotion) {
            if (!in_array($replacedByMotion->status, $invisibleStatuses)) {
                $directDescendant = $replacedByMotion;
            }
            $subDescendant = static::findMostRecentVersionOfMotion($replacedByMotion, $userVisibleOnly) ?? $subDescendant;
        }

        if ($subDescendant) {
            return $subDescendant;
        } else {
            return $directDescendant;
        }
    }

    public static function updateAllVersionsOfMotion(Motion $motion, bool $onlyConsultation, callable $updater): void
    {
        $motion->getMyConsultation()->refresh();

        $consultationId = $motion->consultationId;
        foreach (self::getSortedHistoryForMotion($motion, false) as $motion) {
            if ($onlyConsultation && $motion->consultationId !== $consultationId) {
                continue;
            }
            $updater($motion);
        }
    }
}
