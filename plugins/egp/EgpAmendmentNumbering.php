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
        $prefixes = [];
        foreach ($motion->amendments as $amend) {
            $prefixes[] = $amend->titlePrefix;
        }
        $maxRev = static::getMaxTitlePrefixNumber($prefixes);
        $prefix = 'AM' . ($maxRev + 1);

        $initiatorOrgas = [];
        foreach ($amendment->getInitiators() as $initiator) {
            if ($initiator->organization) {
                $initiatorOrgas[] = $initiator->organization;
            }
        }
        if (count($initiatorOrgas) > 0) {
            $prefix .= ' (' . implode(", ", $initiatorOrgas) . ')';
        }

        return $prefix;
    }
}
