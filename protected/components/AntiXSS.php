<?php

class AntiXSS
{
	private static $seed = null;

	/**
	 * @static
	 * @return string
	 */
	private static function getSeed()
	{
		if (static::$seed === null) static::$seed = SEED_KEY;
		return static::$seed;
	}

	/**
	 * @static
	 * @return string
	 */
	private static function getUserPart()
	{
		$ip = (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "");
		if (strpos($ip, "195.93.") === 0) $ip = "195.93."; // AOL
		$forwardfor = (isset($_SERVER["X_FORWARDED_FOR"]) ? $_SERVER["X_FORWARDED_FOR"] : "");
		return $ip . $forwardfor;
	}

	/**
	 * @static
	 * @param string $formname
	 * @param int $days_ago
	 * @return string
	 */
	static function createToken($formname = "", $days_ago = 0)
	{
		return $formname . "_" . substr(md5($formname . static::getSeed() . date("Ymd", time() - 3600 * 24 * $days_ago) . static::getUserPart()), 0, 10);
	}

	/**
	 * @static
	 * @param string $formname
	 * @return bool
	 */
	static function isTokenSet($formname = "")
	{
		if (isset($_REQUEST[static::createToken($formname, 0)])) return true;
		return isset($_REQUEST[static::createToken($formname, 1)]);
	}

	/**
	 * @static
	 * @param string $formname
	 * @return null|string
	 */
	static function getTokenVal($formname = "")
	{
		if (isset($_REQUEST[static::createToken($formname, 0)])) return $_REQUEST[static::createToken($formname, 0)];
		return (isset($_REQUEST[static::createToken($formname, 1)]) ? $_REQUEST[static::createToken($formname, 1)] : null);
	}
}
