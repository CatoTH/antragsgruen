<?php

class AntragPDFWidget extends CWidget
{
	/** @var Antrag */
	public $antrag;

	/** @var TCPDF */
	public $pdf;

	/** @var Sprache */
	public $sprache;

	/** @var string */
	public $initiatorinnen;

	/** @var bool */
	public $header = true;

	public function run()
	{
		$this->render('antrag_pdf', array(
			"pdf" => $this->pdf,
			"sprache" => $this->sprache,
			"antrag" => $this->antrag,
			"initiatorinnen" => $this->initiatorinnen,
			"header" => $this->header
		));
	}
}