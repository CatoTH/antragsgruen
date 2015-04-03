<?php
namespace app\models\amendmentNumbering;

use app\models\db\Amendment;
use app\models\db\Motion;

class ByLine extends IAmendmentNumbering
{

    /**
     * @return string
     */
    public static function getName()
    {
        return 'A1-Ä70-1 (Zählung nach betroffener Zeile)';
    }

    /**
     * @return int
     */
    public static function getID()
    {
        return 2;
    }

    /**
     * @param Amendment $amendment
     * @param Motion $motion
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentNumber(Amendment $amendment, Motion $motion)
    {
        $line    = $amendment->getFirstDiffLine();
        $revBase = $motion->titlePrefix . "-Ä" . $line . "-";
        $maxRev  = 0;
        foreach ($motion->amendments as $amend) {
            $x = explode($revBase, $amend->titlePrefix);
            if (count($x) == 2 && $x[1] > $maxRev) {
                $maxRev = IntVal($x[1]);
            }
        }
        return $revBase . ($maxRev + 1);
    }
}
