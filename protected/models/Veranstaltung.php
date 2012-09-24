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
	public function getPolicyAntraege() {
		if (is_null($this->policy_antraege_obj)) $this->policy_antraege_obj = IPolicyAntraege::getInstanceByID($this->policy_antraege);
		return $this->policy_antraege_obj;
	}

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

	public function darfEroeffnenAntrag()
	{
		if ($this->antragsschluss != "" && date("YmdHis") > str_replace(array(" ", ":", "-"), array("", "", ""), $this->antragsschluss)) return false;
		return $this->getPolicyAntraege()->checkCurUserHeuristically();
	}

	public function darfEroeffnenAenderungsAntrag()
	{
		if ($this->antragsschluss != "" && date("YmdHis") > str_replace(array(" ", ":", "-"), array("", "", ""), $this->antragsschluss)) return false;
		return $this->darfEroeffnen_intern($this->policy_aenderungsantraege);
	}

	public function darfEroeffnenKommentar()
	{
		return $this->darfEroeffnen_intern($this->policy_kommentare);
	}

	/**
	 * @var $clasName string
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}
}