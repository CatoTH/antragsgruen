<?php

Yii::import('application.models._base.BaseVeranstaltung');

class Veranstaltung extends BaseVeranstaltung
{

	/** @var null|IPolicyAntraege */
	private $policy_antraege_obj = null;

	public static $POLICY_NUR_ADMINS = 0;
	public static $POLICY_PARTEIMITGLIEDER = 1;
	public static $POLICY_REGISTRIERTE = 2;
	public static $POLICY_ALLE = 3;
	public static $POLICIES = array(
		0 => "Nur Admins",
		1 => "Nur Parteimitglieder (Wurzelwerk)",
		2 => "Nur eingeloggte Benutzer",
		3 => "Alle",
	);

	public static $TYP_PARTEITAG = 0;
	public static $TYP_PROGRAMM = 1;
	public static $TYPEN = array(
		0 => "Parteitag",
		1 => "(Wahl-)Programm",
	);

	/** @return IPolicyAntraege */
	public function getPolicyAntraege()
	{
		if (is_null($this->policy_antraege_obj)) $this->policy_antraege_obj = IPolicyAntraege::getInstanceByID($this->policy_antraege);
		return $this->policy_antraege_obj;
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
			case Veranstaltung::$POLICY_NUR_ADMINS:
				if (Yii::app()->user->isGuest) return false;
				return (Yii::app()->user->getState("role") == "admin");
			case Veranstaltung::$POLICY_PARTEIMITGLIEDER:
				if (Yii::app()->user->isGuest) return false;
				return (preg_match("/^openid:https:\/\/[a-z0-9_-]+\.netzbegruener\.in\/$/i", Yii::app()->user->id));
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function darfEroeffnenAntrag()
	{
		if ($this->antragsschluss != "" && date("YmdHis") > str_replace(array(" ", ":", "-"), array("", "", ""), $this->antragsschluss)) return false;
		return $this->darfEroeffnen_intern($this->policy_antraege);
	}

	/**
	 * @return bool
	 */
	public function darfEroeffnenAenderungsAntrag()
	{
		if ($this->antragsschluss != "" && date("YmdHis") > str_replace(array(" ", ":", "-"), array("", "", ""), $this->antragsschluss)) return false;
		return $this->darfEroeffnen_intern($this->policy_aenderungsantraege);
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
	 * @var string $className
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
}