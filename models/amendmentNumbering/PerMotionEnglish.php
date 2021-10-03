<?php
namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, Motion};

class PerMotionEnglish extends IAmendmentNumbering
{
    public static function getName(): string
    {
        return \Yii::t('structure', 'amend_number_english');
    }

    public static function getID(): int
    {
        return 3;
    }

    public function getAmendmentNumber(Amendment $amendment, Motion $motion): string
    {
        $prefixes = [];
        foreach ($motion->amendments as $amend) {
            $prefixes[] = $amend->titlePrefix;
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);

        $str = trim($motion->titlePrefix);
        if ($str) {
            $str .= ' ';
        }
        $str .= 'A' . ($maxRev + 1);

        return $str;
    }
}
