<?php

class VeranstaltungsEinstellungen extends CFormModel
{
	/** @var bool */
	public $antrag_neu_braucht_email = false;
	public $antrag_neu_braucht_telefon = false;
	public $antrag_neu_kann_telefon = false;
	public $kommentar_neu_braucht_email = false;

	public $initiatorInnen_duerfen_aendern = false;
	public $admins_duerfen_aendern = true;

	public $wartungs_modus_aktiv = false;
	public $bestaetigungs_emails = false;
	public $zeilen_nummerierung_global = false;
	public $ae_nummerierung_global = false;
	public $ae_nummerierung_nach_zeile = false;
	public $revision_name_verstecken = false;
	public $ansicht_minimalistisch = false;
	public $feeds_anzeigen = true;
	public $kommentare_unterstuetzbar = false;
	public $freischaltung_antraege = false;
	public $freischaltung_aenderungsantraege = false;
	public $freischaltung_kommentare = false;
	public $initiatorInnen_duerfen_aes_ablehnen = false;
	public $titel_eigene_zeile = true;
	public $kann_pdf = true;
	public $zeilenlaenge = 80;
	public $begruendung_in_html = false;
	public $bdk_startseiten_layout = false;
	public $antragstext_max_len = 0;
	public $antrag_neu_button_label = "";
	public $antrag_begruendungen = true;

	/** @var array */
	public $antrags_typen_deaktiviert = array();


	/** @var null|string */
	public $logo_url = null;
	public $fb_logo_url = null;
	public $antrag_einleitung = null;

	/**
	 * @param string|null $data
	 */
	public function __construct($data)
	{
		if ($data == "") return;
		$data = (array)json_decode($data);

		if (!is_array($data)) return;
		foreach ($data as $key => $val) if (property_exists($this, $key)) $this->$key = $val;
	}

	/**
	 * @return string
	 */
	public function toJSON()
	{
		return json_encode(get_object_vars($this));
	}

	/**
	 * @param array $formdata
	 */
	public function saveForm($formdata)
	{
		$fields = get_object_vars($this);
		foreach ($fields as $key => $val) if (isset($formdata[$key])) {
			if (is_bool($val)) $this->$key = (bool)$formdata[$key];
			elseif (is_int($val)) $this->$key = (int)$formdata[$key];
			else $this->$key = $formdata[$key];
		} elseif (is_bool($val)) $this->$key = false; // Checkbox nicht gesetzt

		if (isset($_REQUEST["antrags_typen_aktiviert"])) {
			$this->antrags_typen_deaktiviert = array();
			foreach (Antrag::$TYPEN as $id => $name) if (!in_array($id, $_REQUEST["antrags_typen_aktiviert"])) $this->antrags_typen_deaktiviert[] = IntVal($id);
		}

	}


	public function attributeLabels()
	{
		return array(
			'ae_nummerierung_global'     => 'ÄA-Nummerierung für die ganze Veranstaltung',
			'zeilen_nummerierung_global' => 'Zeilennummerierung durchgehend für die ganze Veranstaltung',
			'bestaetigungs_emails'       => 'Bestätigungs-E-Mails an die NutzerInnen schicken'
		);
	}
}
