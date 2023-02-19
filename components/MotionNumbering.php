<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\Motion;

class MotionNumbering
{
    public static function getNewTitlePrefixInternal(string $titlePrefix): string
    {
        $new      = \Yii::t('motion', 'prefix_new_code');
        $newMatch = preg_quote($new, '/');
        if (preg_match('/' . $newMatch . '/i', $titlePrefix)) {
            /** @var string[] $parts */
            $parts = preg_split('/(' . $newMatch . '\s*)/i', $titlePrefix, -1, PREG_SPLIT_DELIM_CAPTURE);
            $last  = (int)array_pop($parts);
            $last  = ($last > 0 ? $last + 1 : 2); // NEW BLA -> NEW 2
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
