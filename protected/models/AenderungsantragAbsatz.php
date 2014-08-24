<?php


class AenderungsantragAbsatz
{

	/**
	 * @var int
	 */
	public $aenderungsantrag_id;
	/**
	 * @var int
	 */
	public $absatz_nr;

	/**
	 * @var array|AenderungsantragKommentar[]
	 */
	public $kommentare;

	/**
	 * @var string
	 */
	public $str_bbcode_vorher;
	public $str_bbcode_nachher;

	/**
	 * @param string $str_bbcode_vorher
	 * @param string $str_bbcode_nachher
	 * @param int $aenderungsantrag_id
	 * @param int $absatz_nr
	 * @param array|AenderungsantragKommentar[] $kommentare
	 */
	function __construct($str_bbcode_vorher, $str_bbcode_nachher, $aenderungsantrag_id, $absatz_nr, $kommentare)
	{
		$this->str_bbcode_nachher  = $str_bbcode_nachher;
		$this->str_bbcode_vorher   = $str_bbcode_vorher;
		$this->absatz_nr           = $absatz_nr;
		$this->aenderungsantrag_id = $aenderungsantrag_id;
		$this->kommentare          = array();
		foreach ($kommentare as $komm) if ($komm->absatz == $absatz_nr && $komm->istSichtbarCurrUser()) $this->kommentare[] = $komm;
	}

	/**
	 * @return string
	 */
	public function getDiffHTML() {
		$str = DiffUtils::renderBBCodeDiff2HTML($this->str_bbcode_vorher, $this->str_bbcode_nachher, false);
		return $str;
	}
}
