<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;

class AntiXSS
{
    /**
     * @static
     * @return string
     */
    private static function getSeed()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if (in_array($params->randomSeed, array("", "RANDOMSEED"))) {
            die("Please specify a randomSeed in config/config.php\n");
        }
        return $params->randomSeed;
    }

    /**
     * @static
     * @return string
     */
    private static function getUserPart()
    {
        $ipaddr = (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "");
        if (strpos($ipaddr, "195.93.") === 0) {
            $ipaddr = "195.93.";
        } // AOL
        $forwardfor = (isset($_SERVER["X_FORWARDED_FOR"]) ? $_SERVER["X_FORWARDED_FOR"] : "");
        return $ipaddr . $forwardfor;
    }

    /**
     * @static
     * @param string $formname
     * @param int $days_ago
     * @return string
     */
    public static function createToken($formname = "", $days_ago = 0)
    {
        $date = date("Ymd", time() - 3600 * 24 * $days_ago);
        $str  = $formname . static::getSeed() . $date . static::getUserPart();
        return $formname . "_" . substr(md5($str), 0, 10);
    }

    /**
     * @static
     * @param string $formname
     * @return bool
     */
    public static function isTokenSet($formname = "")
    {
        if (isset($_REQUEST[static::createToken($formname, 0)])) {
            return true;
        }
        return isset($_REQUEST[static::createToken($formname, 1)]);
    }

    /**
     * @static
     * @param string $formname
     * @return null|string
     */
    public static function getTokenVal($formname = "")
    {
        if (isset($_REQUEST[static::createToken($formname, 0)])) {
            return $_REQUEST[static::createToken($formname, 0)];
        }
        $tok = static::createToken($formname, 1);
        return (isset($_REQUEST[$tok]) ? $_REQUEST[$tok] : null);
    }
}
