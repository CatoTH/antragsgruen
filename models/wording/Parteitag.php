<?php

namespace app\models\wording;


class Parteitag extends IWording
{
    /**
     * @static
     * @abstract
     * @return int
     */
    public static function getWordingID()
    {
        return 0;
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getWordingName()
    {
        return "Parteitag";
    }
}
