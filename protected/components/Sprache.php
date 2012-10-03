<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tobias
 * Date: 03.10.12
 * Time: 08:20
 * To change this template use File | Settings | File Templates.
 */
class Sprache
{
	/**
	 * @var array|string[]
	 */
	public $vars = array();

	/**
	 * @param string $text
	 * @return string
	 */
	public function get($text) {
		if (isset($this->vars[$text])) return $this->vars[$text];
		else return $text;
	}
}
