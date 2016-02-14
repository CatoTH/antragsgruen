<?php

namespace app\components\wordpress;

class WordpressLayoutData {
	/** @var string */
	public $content;
	public $sidebar;
	public $jsFiles;
	public $cssFiles;
	public $onLoadJs;

	/** @var null|WordpressLayoutData */
	protected static $instance = null;

	/**
	 * @return WordpressLayoutData
	 */
	public static function getInstance() {
		if ( static::$instance == null ) {
			static::$instance = new WordpressLayoutData();
		}

		return static::$instance;
	}
}
