<?php

declare(strict_types=1);

namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, IMotion, Motion};

class GlobalCompact extends IAmendmentNumbering
{
    public static function getName(): string
    {
        return \Yii::t('structure', 'amend_number_global');
    }

    public static function getID(): int
    {
        return 1;
    }

    /**
     * @param Amendment[] $otherAmendments
     */
    public function getAmendmentNumber(Amendment $amendment, IMotion $baseImotion, array $otherAmendments): string
    {
        $prefixes = [];
        foreach ($baseImotion->getMyConsultation()->motions as $mot) {
            foreach ($mot->amendments as $amend) {
                $prefixes[] = $amend->titlePrefix;
            }
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);
        return \Yii::t('amend', 'amendment_prefix') . ($maxRev + 1);
    }

    public function findAmendmentWithPrefix(Motion $motion, string $prefix, ?Amendment $ignore = null): ?Amendment
    {
        $prefixNorm = trim(mb_strtoupper($prefix));
        foreach ($motion->getMyConsultation()->motions as $mot) {
            foreach ($mot->amendments as $amend) {
                $amendPrefixNorm = trim(mb_strtoupper($amend->titlePrefix ?? ''));
                if ($amendPrefixNorm != '' && $amendPrefixNorm === $prefixNorm) {
                    if ($ignore === null || $ignore->id != $amend->id) {
                        return $amend;
                    }
                }
            }
        }
        return null;
    }
}
