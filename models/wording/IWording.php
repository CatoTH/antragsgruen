<?php

namespace app\models\wording;


abstract class IWording
{
    const WORDING_PARTEITAG = 0;

    /**
     * @return IWording[]
     */
    public static function getWordings()
    {
        return [
            static::WORDING_PARTEITAG    => Parteitag::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getWordingNames()
    {
        $names = [];
        foreach (static::getWordings() as $key => $pol) {
            $names[$key] = $pol::getWordingName();
        }
        return $names;
    }



    protected $translations = [];

    /**
     * @param string $strTitle
     * @return string
     */
    public function get($strTitle)
    {
        if (isset($this->translations[$strTitle])) {
            return $this->translations[$strTitle];
        }
        return $strTitle;
    }


    /**
     * @static
     * @abstract
     * @return int
     */
    public static function getWordingID()
    {
        return -1;
    }

    /**
     * @static
     * @abstract
     * @return string
     */
    public static function getWordingName()
    {
        return "";
    }
}
