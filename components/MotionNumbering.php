<?php

declare(strict_types=1);

namespace app\components;

class MotionNumbering
{
    public static function getNewTitlePrefixInternal(string $titlePrefix): string
    {
        $new      = \Yii::t('motion', 'prefix_new_code');
        $newMatch = preg_quote($new, '/');
        if (preg_match('/' . $newMatch . '/i', $titlePrefix)) {
            /** @var string[] $parts */
            $parts = preg_split('/(' . $newMatch . '\s*)/i', $titlePrefix, -1, PREG_SPLIT_DELIM_CAPTURE);
            $last  = (int)array_pop($parts);
            $last  = ($last > 0 ? $last + 1 : 2); // NEW BLA -> NEW 2
            $parts[] = $last;

            return implode("", $parts);
        } else {
            return $titlePrefix . $new;
        }
    }

    public static function getNewVersion(string $version): string
    {
        if (preg_match("/^(?<pre>.*?)(?<version>\d+)$/siu", $version, $matches)) {
            $newVersion = (int)$matches['version'] + 1;
            return $matches['pre'] . $newVersion;
        } else {
            return $version . '2';
        }
    }
}
