<?php

class VeranstaltungsreihenEinstellungen {
	public $wartungs_modus_aktiv = false;

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
