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
        return 'Ä1 zu A1 (Zählung pro Antrag)';
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
        $maxRev = $this->getMaxAmendmentRevNr($motion);
        return "Ä" . ($maxRev + 1);
    }
}
