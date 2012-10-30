<?php

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
