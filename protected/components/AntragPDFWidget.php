<?php

class AntragPDFWidget extends CWidget
{
	/** @var Antrag */
	public $antrag;

	/** @var TCPDF */
	public $pdf;

	/** @var Sprache */
	public $sprache;

	/** @var Person */
	public $initiator;

	public function run()
	{
		$this->render('antrag_pdf', array(
			"pdf" => $this->pdf,
			"sprache" => $this->sprache,
			"antrag" => $this->antrag,
			"initiator" => $this->initiator
		));
	}
}