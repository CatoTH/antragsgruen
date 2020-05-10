<?php
namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, Motion};

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

    public function getAmendmentNumber(Amendment $amendment, Motion $motion): string
    {
        $prefixes = [];
        foreach ($motion->amendments as $amend) {
            $prefixes[] = $amend->titlePrefix;
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);
        return 'Ä' . ($maxRev + 1);
    }
}
