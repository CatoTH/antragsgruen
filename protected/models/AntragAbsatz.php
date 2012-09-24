<?php


class AntragAbsatz
{

	/**
	 * @var array|string[]
	 */
	public $zeilen;
	/**
	 * @var int
	 */
	public $antrag_id;
	/**
	 * @var int
	 */
	public $absatz_nr;
	/**
	 * @var array|AntragKommentar[]
	 */
	public $kommentare;
	/**
	 * @var array|Aenderungsantrag[]
	 */
	public $aenderungsantraege;

	/**
	 * @var string
	 */
	public $str_html;
	/**
	 * @var string
	 */
	public $str_html_plain;
	/**
	 * @var string
	 */
	public $str_bbcode;

	/**
	 * @param string $str_html
	 * @param string $str_html_plain
	 * @param string $str_bbcode
	 * @param int $antrag_id
	 * @param int $absatz_nr
	 * @param array|AntragKommentar[] $kommentare
	 * @param array|Aenderungsantrag[] $aenderungsantraege
	 */
	function __construct($str_html, $str_html_plain, $str_bbcode, $antrag_id, $absatz_nr, $kommentare, $aenderungsantraege) {
		$this->str_html = $str_html;
		$this->str_bbcode = $str_bbcode;
		$this->str_html_plain = $str_html_plain;
		$this->absatz_nr = $absatz_nr;
		$this->antrag_id = $antrag_id;
		$this->kommentare = array();
		$this->aenderungsantraege = array();
		foreach ($kommentare as $komm) if ($komm->absatz == $absatz_nr && $komm->status == IKommentar::$STATUS_FREI) $this->kommentare[] = $komm;
		foreach ($aenderungsantraege as $ant) if (in_array($absatz_nr, $ant->getAffectedParagraphs())) $this->aenderungsantraege[] = $ant;
	}
}
