<?php

class UnterstuetzungsWidget extends CWidget
{
	/** @var IAntrag */
	public $antrag;

	public function run()
	{
		$unterstuetzerInnen = $this->antrag->getUnterstuetzerInnen();
		$this->render('unterstuetzen', array(
			"unterstuetzerInnen" => $unterstuetzerInnen
		));
	}
}