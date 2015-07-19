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
     * @param string[] $prefixes
     */
    public static function getMaxTitlePrefixNumber($prefixes)
    {
        $maxRev = 0;
        $splitStrs = ['neu'];

        foreach ($prefixes as $prefix) {
            foreach ($splitStrs as $split) {
                $spl = explode($split, $prefix);
                $prefix = $spl[0];
            }
            $number = preg_replace('/^(.*[^0-9])?([0-9]+)([^0-9]*)$/siu', '$2', $prefix);
            if ($number > $maxRev) {
                $maxRev = $number;
            }
        }
        return $maxRev;
    }

    /**
     * @param Amendment $amendment
     * @param Motion $motion
     * @return string
     */
    abstract public function getAmendmentNumber(Amendment $amendment, Motion $motion);
}
