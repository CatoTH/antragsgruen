<?php
namespace app\models\amendmentNumbering;


use app\models\db\Amendment;
use app\models\db\Motion;

class GlobalCompact extends IAmendmentNumbering
{

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Ä1 zu A1 (Globale Zählung)';
    }

    /**
     * @return int
     */
    public static function getID()
    {
        return 1;
    }


    /**
     * @param Amendment $amendment
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentNumber(Amendment $amendment, Motion $motion)
    {
        $maxRev = 0;
        foreach ($motion->consultation->motions as $motion) {
            $m = $this->getMaxAmendmentRevNr($motion);
            if ($m > $maxRev) {
                $maxRev = $m;
            }
        }
        return "Ä" . ($maxRev + 1);
    }
}
