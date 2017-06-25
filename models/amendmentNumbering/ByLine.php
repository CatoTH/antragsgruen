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
        return \Yii::t('structure', 'amend_number_perline');
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
        $line = $amendment->getFirstDiffLine();
        while (mb_strlen($line) < $lineStrLen) {
            $line = '0' . $line;
        }
        $revBase = $motion->titlePrefix . '-' . $line;
        $maxRev  = 0;
        foreach ($motion->amendments as $amend) {
            if ($amend->titlePrefix) {
                $x = explode($revBase, $amend->titlePrefix);
                if (count($x) == 2) {
                    if (strlen($x[1]) > 0 && $x[1][0] == '-') {
                        $x[1] = substr($x[1], 1);
                    }
                    $maxRev = max($maxRev, strlen($x[1]) == 0 ? 1 : IntVal($x[1]));
                }
            }
        }
        return $maxRev == 0 ? $revBase : $revBase . '-' . ($maxRev + 1);
    }
}
