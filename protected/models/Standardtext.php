<?php

class Standardtext {
	private $id, $text, $edit_link, $is_fallback, $html;


	/**
	 * @param string $id
	 * @param string $text
	 * @param boolean $html
	 * @param array|null $edit_link
	 * @param boolean $is_fallback
	 */
	public function __construct($id, $text, $html, $edit_link, $is_fallback) {
		$this->id = $id;
		$this->text = $text;
		$this->html = $html;
		$this->edit_link = $edit_link;
		$this->is_fallback = $is_fallback;
	}


	/**
	 * @return bool
	 */
	public function isFallback() { return $this->is_fallback; }

	/**
	 * @return string
	 */
	public function getId() { return $this->id; }

	/**
	 * @return string
	 */
	public function getText() { return $this->text; }

	/**
	 * @return string
	 */
	public function getHTMLText()  {
		if ($this->html) return $this->text;
		else return nl2br(CHtml::encode($this->text));
	}

	/**
	 * @return null|array
	 */
	public function getEditLink() { return $this->edit_link; }

	/**
	 * @return bool
	 */
	public function isHTML() { return $this->html; }
}