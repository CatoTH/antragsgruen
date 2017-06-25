<?php
namespace app\models\amendmentNumbering;

use app\models\db\Amendment;
use app\models\db\Motion;

class PerMotionCompact extends IAmendmentNumbering
{

    /**
     * @return string
     */
    public static function getName()
    {
        return \Yii::t('structure', 'amend_number_permotion');
    }

    /**
     * @return int
     */
    public static function getID()
    {
        return 0;
    }

    /**
     * @param Amendment $amendment
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentNumber(Amendment $amendment, Motion $motion)
    {
        $prefixes = [];
        foreach ($motion->amendments as $amend) {
            $prefixes[] = $amend->titlePrefix;
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);
        return 'Ä' . ($maxRev + 1);
    }
}
