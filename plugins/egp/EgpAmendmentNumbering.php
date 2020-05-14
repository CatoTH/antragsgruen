<?php

declare(strict_types=1);

namespace app\plugins\egp;

use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\{Amendment, Motion};

class EgpAmendmentNumbering extends IAmendmentNumbering
{
    public static function getName(): string
    {
        return 'AM-01 + Organization name';
    }

    public static function getID(): int
    {
        return -2;
    }

    public function getAmendmentNumber(Amendment $amendment, Motion $motion): string
    {
        $line = $amendment->getFirstDiffLine();
        $revBase = 'AM-' . $line;
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
        return $revBase . '-' . ($maxRev + 1);
    }
}
