<?php


class AntragAbsatz
{

	/**
	 * @var int
	 */
	public $antrag_id;
	/**
	 * @var int
	 */
	public $absatz_nr;
	/**
	 * @var int
	 */
	public $anzahl_zeilen;
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
		foreach ($kommentare as $komm) if ($komm->absatz == $absatz_nr && $komm->istSichtbarCurrUser()) $this->kommentare[] = $komm;
		foreach ($aenderungsantraege as $ant) if (in_array($absatz_nr, $ant->getAffectedParagraphs())) $this->aenderungsantraege[] = $ant;
		$this->anzahl_zeilen = substr_count($this->str_html, "<span class='zeilennummer'>");

		usort($this->aenderungsantraege, function($ae1, $ae2) use ($absatz_nr) {
			/** @var Aenderungsantrag $ae1 */
			/** @var Aenderungsantrag $ae2 */
			$ae_zeile1 = $ae1->getFirstAffectedLineOfParagraph_relative($absatz_nr);
			$ae_zeile2 = $ae2->getFirstAffectedLineOfParagraph_relative($absatz_nr);
			if ($ae_zeile1 > $ae_zeile2) return 1;
			if ($ae_zeile1 < $ae_zeile2) return -1;
			return 0;
		});
	}
}
