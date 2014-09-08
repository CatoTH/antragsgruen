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
	 * @var int|null
	 */
	public $zeile_von;
	public $zeile_bis;

	/**
	 * @param string $str_bbcode_vorher
	 * @param string $str_bbcode_nachher
	 * @param int $aenderungsantrag_id
	 * @param int $absatz_nr
	 * @param int|null $zeile_von
	 * @param int|null $zeile_bis
	 * @param array|AenderungsantragKommentar[] $kommentare
	 */
	function __construct($str_bbcode_vorher, $str_bbcode_nachher, $aenderungsantrag_id, $absatz_nr, $zeile_von, $zeile_bis, $kommentare)
	{
		$this->str_bbcode_nachher  = $str_bbcode_nachher;
		$this->str_bbcode_vorher   = $str_bbcode_vorher;
		$this->absatz_nr           = $absatz_nr;
		$this->aenderungsantrag_id = $aenderungsantrag_id;
		$this->zeile_von           = $zeile_von;
		$this->zeile_bis           = $zeile_bis;
		$this->kommentare          = array();
		foreach ($kommentare as $komm) if ($komm->absatz == $absatz_nr && $komm->istSichtbarCurrUser()) $this->kommentare[] = $komm;
	}

	/**
	 * @return string
	 */
	public function getDiffHTML()
	{
		if ($this->zeile_von !== null && $this->zeile_bis !== null) {
			$str_pre = "<div class='ae_absatz_header'>Im Absatz von Zeile " . $this->zeile_von ." bis " . $this->zeile_bis . "</div>\n";
		} else {
			$str_pre = "";
		}
		$str = DiffUtils::renderBBCodeDiff2HTML($this->str_bbcode_vorher, $this->str_bbcode_nachher, false, 0, $str_pre);
		return $str;
	}
}
