<?php

class VeranstaltungsreihenEinstellungen {

	/** @var bool */
	public $wartungs_modus_aktiv = false;
	public $rechnung_gestellt = false;
	public $rechnung_bezahlt = false;

	/** @var int */
	public $bereit_zu_zahlen = 0;

	public static $BEREIT_ZU_ZAHLEN_NEIN = 0;
	public static $BEREIT_ZU_ZAHLEN_VIELLEICHT = 1;
	public static $BEREIT_ZU_ZAHLEN_JA = 2;
	public static $BEREIT_ZU_ZAHLEN = array(
		2 => "Ja",
		0 => "Nein",
		1 => "Will mich später entscheiden"
	);

	/**
	 * @param string|null $data
	 */
	public function __construct($data) {
		if ($data == "") return;
		$data = (array)json_decode($data);

		if (!is_array($data)) return;
		foreach ($data as $key => $val) $this->$key = $val;
	}

	/**
	 * @return string
	 */
	public function toJSON() {
		return json_encode(get_object_vars($this));
	}

	/**
	 * @param array $formdata
	 */
	public function saveForm($formdata) {
		$fields = get_object_vars($this);
		foreach ($fields as $key=>$val) if (isset($formdata[$key])) {
			if (is_bool($val)) $this->$key = (bool)$formdata[$key];
			elseif (is_int($val)) $this->$key = (int)$formdata[$key];
			else $this->$key = $formdata[$key];
		}
	}
}
