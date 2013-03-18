<?php

Yii::import('application.models._base.BaseVeranstaltung');

class Veranstaltung extends BaseVeranstaltung
{

	/** @var null|IPolicyAntraege */
	private $policy_antraege_obj = null;
	private $policy_aenderungsantraege_obj = null;

	/** @var null|IPolicyUnterstuetzen */
	private $policy_unterstuetzen_obj = null;

	public static $POLICY_NUR_ADMINS = 0;
	public static $POLICY_PARTEIMITGLIEDER = 1;
	public static $POLICY_REGISTRIERTE = 2;
	public static $POLICY_ALLE = 3;
	public static $POLICY_NIEMAND = 4;
	public static $POLICY_ALLE_FREISCHALTUNG = 5;
	public static $POLICIES = array(
		4 => "Niemand",
		0 => "Nur Admins",
		1 => "Nur Parteimitglieder (Wurzelwerk)",
		2 => "Nur eingeloggte Benutzer",
		5 => "Alle (mit Freischaltung)",
		3 => "Alle",
	);

	public static $TYP_PARTEITAG = 0;
	public static $TYP_PROGRAMM = 1;
	public static $TYPEN = array(
		0 => "Parteitag",
		1 => "(Wahl-)Programm",
	);


	/** @var null|VeranstaltungsEinstellungen */
	private $einstellungen_object = null;

	/**
	 * @return VeranstaltungsEinstellungen
	 */
	public function getEinstellungen() {
		if (!is_object($this->einstellungen_object)) $this->einstellungen_object = new VeranstaltungsEinstellungen($this->einstellungen);
		return $this->einstellungen_object;
	}

	/**
	 * @param VeranstaltungsEinstellungen $einstellungen
	 */
	public function setEinstellungen($einstellungen) {
		$this->einstellungen_object = $einstellungen;
		$this->einstellungen = $einstellungen->toJSON();
	}

	/** @return IPolicyAntraege */
	public function getPolicyAntraege()
	{
		if (is_null($this->policy_antraege_obj)) $this->policy_antraege_obj = IPolicyAntraege::getInstanceByID($this->policy_antraege, $this);
		return $this->policy_antraege_obj;
	}

	/** @return IPolicyAntraege */
	public function getPolicyAenderungsantraege() {
		if (is_null($this->policy_aenderungsantraege_obj)) $this->policy_aenderungsantraege_obj = IPolicyAntraege::getInstanceByID($this->policy_aenderungsantraege, $this);
		return $this->policy_aenderungsantraege_obj;
	}

	/** @return IPolicyUnterstuetzen */
	public function getPolicyUnterstuetzen() {
		if (is_null($this->policy_unterstuetzen_obj)) $this->policy_unterstuetzen_obj = IPolicyUnterstuetzen::getInstanceByID($this->policy_unterstuetzen, $this);
		return $this->policy_unterstuetzen_obj;
	}

	/**
	 * @param int $policy
	 * @return bool
	 */
	private function darfEroeffnen_intern($policy)
	{
		switch ($policy) {
			case Veranstaltung::$POLICY_ALLE:
				return true;
			case Veranstaltung::$POLICY_ALLE_FREISCHALTUNG:
				return true;
			case Veranstaltung::$POLICY_REGISTRIERTE:
				return !Yii::app()->user->isGuest;
			case Veranstaltung::$POLICY_NUR_ADMINS:
				if (Yii::app()->user->isGuest) return false;
				return (Yii::app()->user->getState("role") == "admin");
			case Veranstaltung::$POLICY_PARTEIMITGLIEDER:
				if (Yii::app()->user->isGuest) return false;
				return (preg_match("/^openid:https:\/\/[a-z0-9_-]+\.netzbegruener\.in\/$/i", Yii::app()->user->id));
			case Veranstaltung::$POLICY_NIEMAND:
				return false;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function darfEroeffnenKommentar()
	{
		return $this->darfEroeffnen_intern($this->policy_kommentare);
	}


	/**
	 * @return Sprache
	 */
	public function getSprache()
	{
		switch ($this->typ) {
			case Veranstaltung::$TYP_PROGRAMM:
				return new SpracheProgramm();
			default:
				return new SpracheAntraege();
		}
	}


	/**
	 * @return array|string[]
	 */
	public static function getStandardtextIDs() {
		return array("startseite", "impressum", "hilfe", "antrag_confirm");
	}

	/**
	 * @return array|string[]
	 */
	public static function getHTMLStandardtextIDs() {
		return array("startseite", "impressum", "hilfe", "antrag_eingereicht", "antrag_confirm", "ae_eingereicht", "ae_confirm");
	}

	/**
	 * @param string $id
	 * @return Standardtext
	 */
	public function getStandardtext($id) {
		$vtext = Texte::model()->findByAttributes(array("text_id" => $id, "veranstaltung_id" => $this->id));
		/** @var Texte|null $vtext */

		if (is_null($vtext)) {
			$edit_link = array("admin/texte/create", array("key"  => $id, "veranstaltung_id" => $this->yii_url));
			$vtext = Texte::model()->findByAttributes(array("text_id" => $id, "veranstaltung_id" => null));
			$is_fallback = true;
		} else {
			$edit_link = array("admin/texte/update", array("id" => $vtext->id, "veranstaltung_id" => $this->yii_url));
			$is_fallback = false;
		}

		$text = (is_null($vtext) ? "" : $vtext->text);

		if (!$this->isAdminCurUser()) $edit_link = null;

		$html = in_array($id, Veranstaltung::getHTMLStandardtextIDs());

		return new Standardtext($id, $text, $html, $edit_link, $is_fallback);
	}


	/**
	 * @param int $antrag_typ
	 * @return string
	 */
	public function naechsteAntragRevNr($antrag_typ) {
		$max_rev     = 0;
		$andereantrs = $this->antraege;
		foreach ($andereantrs as $antr) if ($antr->typ == $antrag_typ) {
			$revs  = substr($antr->revision_name, strlen(Antrag::$TYP_PREFIX[$antr->typ]));
			$revnr = IntVal($revs);
			if ($revnr > $max_rev) $max_rev = $revnr;
		}
		return Antrag::$TYP_PREFIX[$antrag_typ] . ($max_rev + 1);
	}

	/**
	 * @return array|array[]
	 */
	public function antraegeSortiert() {
		$antraege        = $this->antraege;
		$antraege_sorted = array();
		// $warnung         = false;
		foreach ($antraege as $ant) if (!in_array($ant->status, IAntrag::$STATI_UNSICHTBAR)) {
			if (!isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]])) $antraege_sorted[Antrag::$TYPEN[$ant->typ]] = array();
			$key = $ant->revision_name;
			/*
			if (isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key]) && !$warnung) {
				$warnung = true;
				Yii::app()->user->setFlash("error", "Es können nicht alle Anträge angezeigt werden, da mindestens ein Kürzel ($key) mehrfach vergeben ist.");
			}
			*/
			$antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key] = $ant;
		}
		if (!in_array($this->yii_url, array("ltwby13-programm", "btw13-programm"))) foreach ($antraege_sorted as $key=>$val) {
			ksort($antraege_sorted[$key]);
		}
		return $antraege_sorted;
	}

	/**
	 * @param Person $person
	 * @return bool
	 */
	public function isAdmin($person) {
		$ein = VeranstaltungPerson::model()->findAllByAttributes(array(
			"veranstaltung_id" => $this->id,
			"person_id" => $person->id,
			"rolle" => VeranstaltungPerson::$STATUS_ADMIN
		));
		return (count($ein) > 0);
	}

	/**
	 * @return bool
	 */
	public function isAdminCurUser() {
		$user = Yii::app()->user;
		if ($user->isGuest) return false;
		if ($user->getState("role") === "admin") return true;
		$ich = Person::model()->findByAttributes(array("auth" => $user->id));
		/** @var Person $ich  */
		if ($ich == null) return false;
		return $this->isAdmin($ich);
	}

	/**
	 * @var string $className
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	public function save($runValidation = true, $attributes = null) {
		Yii::app()->cache->delete("pdf_" . $this->id);
		return parent::save($runValidation, $attributes);
	}
}