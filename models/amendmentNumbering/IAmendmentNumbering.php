<?php

namespace app\models\amendmentNumbering;

use app\components\UrlHelper;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Motion, Amendment};

abstract class IAmendmentNumbering
{
    /**
     * @return IAmendmentNumbering[]|string[]
     */
    public static function getNumberings(): array
    {
        $numberings = [
            0 => PerMotionCompact::class,
            1 => GlobalCompact::class,
            2 => ByLine::class,
            3 => PerMotionEnglish::class,
        ];

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            foreach ($plugin::getCustomAmendmentNumberings() as $numbering) {
                $numberings[$numbering::getID()] = $numbering;
            }
        }

        return $numberings;
    }

    /**
     * @return string[]
     */
    public static function getNames(): array
    {
        $names = [];
        foreach (static::getNumberings() as $key => $pol) {
            $names[$key] = $pol::getName();
        }
        return $names;
    }

    public static function getID(): int
    {
        return -1;
    }


    public static function getName(): string
    {
        return '';
    }

    /**
     * @param string[]|null[] $prefixes
     */
    public static function getMaxTitlePrefixNumber(array $prefixes): int
    {
        $maxRev    = 0;
        $splitStrs = ['neu'];

        foreach ($prefixes as $prefix) {
            if ($prefix === null) {
                continue;
            }
            foreach ($splitStrs as $split) {
                $spl    = explode($split, $prefix);
                $prefix = $spl[0];
            }
            // Take the last consecutive numbers of the string
            $number = intval(preg_replace('/^(.*[^0-9])?([0-9]+)([^0-9]*)$/siu', '$2', $prefix));
            if ($number > $maxRev) {
                $maxRev = $number;
            }
        }
        return $maxRev;
    }

    abstract public function getAmendmentNumber(Amendment $amendment, Motion $motion): string;

    public function findAmendmentWithPrefix(Motion $motion, string $prefix, ?Amendment $ignore = null): ?Amendment
    {
        $prefixNorm = trim(mb_strtoupper($prefix));
        foreach ($motion->amendments as $amend) {
            $amendPrefixNorm = trim(mb_strtoupper($amend->titlePrefix ?: ''));
            if ($amendPrefixNorm !== '' && $amendPrefixNorm === $prefixNorm) {
                if ($ignore === null || $ignore->id != $amend->id) {
                    return $amend;
                }
            }
        }
        return null;
    }
}
