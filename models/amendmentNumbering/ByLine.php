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
     * @param int $lineStrLen
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentNumber(Amendment $amendment, Motion $motion, $lineStrLen = 3)
    {
        $line    = $amendment->getFirstDiffLine();
        while (mb_strlen($line) < $lineStrLen) {
            $line = '0' . $line;
        }
        $revBase = $motion->titlePrefix . '-' . $line . '-';
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
