<?php

class ZendHelper
{
	private static $zend_initialized = false;

	public static function init()
	{
		if (self::$zend_initialized) return;

		$path = trim(Yii::app()->getBasePath()) . "/../libraries/ZendFramework/library/Zend/";
		require_once($path . "Version/Version.php");
		require_once($path . "Stdlib/ErrorHandler.php");
		require_once($path . "I18n/Translator/TranslatorAwareInterface.php");
		require_once($path . "Validator/ValidatorInterface.php");
		require_once($path . "Validator/AbstractValidator.php");
		require_once($path . "Validator/Ip.php");
		require_once($path . "Validator/Hostname.php");
		require_once($path . "Uri/UriInterface.php");
		require_once($path . "Uri/Uri.php");
		require_once($path . "Uri/Http.php");
		require_once($path . "Uri/UriFactory.php");
		require_once($path . "ServiceManager/ServiceLocatorAwareInterface.php");
		require_once($path . "ServiceManager/ServiceLocatorInterface.php");
		require_once($path . "ServiceManager/ServiceManager.php");
		require_once($path . "ServiceManager/AbstractPluginManager.php");
		require_once($path . "Feed/Exception/ExceptionInterface.php");
		require_once($path . "Feed/Exception/InvalidArgumentException.php");
		require_once($path . "Feed/Writer/Exception/ExceptionInterface.php");
		require_once($path . "Feed/Writer/Exception/InvalidArgumentException.php");
		require_once($path . "Feed/Writer/Extension/RendererInterface.php");
		require_once($path . "Feed/Writer/Extension/AbstractRenderer.php");
		require_once($path . "Feed/Writer/Extension/ITunes/Feed.php");
		require_once($path . "Feed/Writer/Extension/ITunes/Entry.php");
		require_once($path . "Feed/Writer/Extension/ITunes/Renderer/Feed.php");
		require_once($path . "Feed/Writer/Extension/ITunes/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Extension/Atom/Renderer/Feed.php");
		require_once($path . "Feed/Writer/Extension/Content/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Extension/Threading/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Extension/WellFormedWeb/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Extension/Slash/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Extension/DublinCore/Renderer/Feed.php");
		require_once($path . "Feed/Writer/Extension/DublinCore/Renderer/Entry.php");
		require_once($path . "Feed/Writer/Writer.php");
		require_once($path . "Feed/Writer/Entry.php");
		require_once($path . "Feed/Writer/ExtensionManager.php");
		require_once($path . "Feed/Writer/AbstractFeed.php");
		require_once($path . "Feed/Writer/Feed.php");
		require_once($path . "Feed/Writer/Renderer/RendererInterface.php");
		require_once($path . "Feed/Writer/Renderer/AbstractRenderer.php");
		require_once($path . "Feed/Writer/Renderer/Feed/Rss.php");
		require_once($path . "Feed/Writer/Renderer/Entry/Rss.php");
		self::$zend_initialized = true;
	}


	/**
	 * @param string $input
	 * @return int
	 */
	public static function date_iso2timestamp($input)
	{
		$x    = explode(" ", $input);
		$date = array_map("IntVal", explode("-", $x[0]));

		if (count($x) == 2) $time = array_map("IntVal", explode(":", $x[1]));
		else $time = array(0, 0, 0);

		return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
	}

}