<?php

declare(strict_types=1);

namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, IMotion};

class PerMotionCompact extends IAmendmentNumbering
{
    public static function getName(): string
    {
        return \Yii::t('structure', 'amend_number_permotion');
    }

    public static function getID(): int
    {
        return 0;
    }

    /**
     * @param Amendment[] $otherAmendments
     */
    public function getAmendmentNumber(Amendment $amendment, IMotion $baseImotion, array $otherAmendments): string
    {
        $prefixes = [];
        foreach ($otherAmendments as $amend) {
            $prefixes[] = $amend->titlePrefix;
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);
        return \Yii::t('amend', 'amendment_prefix') . ($maxRev + 1);
    }
}
