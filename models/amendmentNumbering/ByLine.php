<?php

declare(strict_types=1);

namespace app\models\amendmentNumbering;

use app\models\db\{Amendment, IMotion};

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

    /**
     * @param Amendment[] $otherAmendments
     */
    public function getAmendmentNumber(Amendment $amendment, IMotion $baseImotion, array $otherAmendments): string
    {
        $line = (string)$amendment->getFirstDiffLine();
        while (grapheme_strlen($line) < 3) {
            $line = '0' . $line;
        }
        $revBase = $baseImotion->titlePrefix . '-' . $line;
        $maxRev  = 0;
        foreach ($otherAmendments as $amend) {
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
