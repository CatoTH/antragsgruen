<?php
namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, Motion};

class ByLine extends IAmendmentNumbering
{

    public static function getName(): string
    {
        return \Yii::t('structure', 'amend_number_perline');
    }

    public static function getID(): int
    {
        return 2;
    }

    public function getAmendmentNumber(Amendment $amendment, Motion $motion, int $lineStrLen = 3): string
    {
        $line = (string)$amendment->getFirstDiffLine();
        while (grapheme_strlen($line) < $lineStrLen) {
            $line = '0' . $line;
        }
        $revBase = $motion->titlePrefix . '-' . $line;
        $maxRev  = 0;
        foreach ($motion->amendments as $amend) {
            if ($amend->titlePrefix) {
                $x = explode($revBase, $amend->titlePrefix);
                if (count($x) === 2) {
                    if (strlen($x[1]) > 0 && $x[1][0] === '-') {
                        $x[1] = substr($x[1], 1);
                    }
                    $maxRev = max($maxRev, strlen($x[1]) === 0 ? 1 : intval($x[1]));
                }
            }
        }
        return $maxRev === 0 ? $revBase : $revBase . '-' . ($maxRev + 1);
    }
}
