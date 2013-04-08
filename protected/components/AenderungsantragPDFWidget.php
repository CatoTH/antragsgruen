<?php

class AenderungsantragPDFWidget extends CWidget
{
	/** @var Aenderungsantrag */
	public $aenderungsantrag;

	/** @var TCPDF */
	public $pdf;

	/** @var Sprache */
	public $sprache;

	/** @var string */
	public $initiatorinnen;

	/** @var bool */
	public $diff_ansicht = false;

	public function run()
	{
		$this->render('aenderungsantrag_pdf', array(
			"pdf" => $this->pdf,
			"sprache" => $this->sprache,
			"aenderungsantrag" => $this->aenderungsantrag,
			"initiatorinnen" => $this->initiatorinnen,
			"diff_ansicht" => $this->diff_ansicht
		));
	}
}