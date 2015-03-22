<?php

namespace app\models\amendmentNumbering;

use app\models\db\Motion;
use app\models\db\Amendment;

abstract class IAmendmentNumbering
{
    /**
     * @return IAmendmentNumbering[]
     */
    public static function getNumberings()
    {
        return [
            0 => PerMotionCompact::class,
            1 => GlobalCompact::class,
            2 => ByLine::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getNames()
    {
        $names = [];
        foreach (static::getNumberings() as $key => $pol) {
            $names[$key] = $pol::getName();
        }
        return $names;
    }

    /**
     * @return int
     */
    public static function getID()
    {
        return -1;
    }


    /**
     * @return string
     */
    public static function getName()
    {
        return '';
    }

    /**
     * @param Motion $motion
     * @return int
     */
    protected function getMaxAmendmentRevNr(Motion $motion)
    {
        $max_rev = 0;
        foreach ($motion->amendments as $amend) {
            // Etwas messy, wg. "Ä" und UTF-8. Alternative Implementierung: auf mbstring.func_overload testen und entsprechend vorgehen
            $index = -1;
            for ($i = 0; $i < strlen($amend->titlePrefix) && $index == -1; $i++) {
                if (is_numeric(substr($amend->titlePrefix, $i, 1))) {
                    $index = $i;
                }
            }
            $revs  = substr($amend->titlePrefix, $index);
            $revnr = IntVal($revs);
            if ($revnr > $max_rev) {
                $max_rev = $revnr;
            }
        }
        return $max_rev;
    }

    /**
     * @param Amendment $amendment
     * @param Motion $motion
     * @return string
     */
    abstract public function getAmendmentNumber(Amendment $amendment, Motion $motion);
}
