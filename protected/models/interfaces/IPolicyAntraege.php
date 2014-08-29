<?php

Yii::import("application.models.policies.*");

abstract class IPolicyAntraege
{
	public static $POLICY_HESSEN_LMV = "HeLMV";
	public static $POLICY_BAYERN_LDK = "ByLDK";
    public static $POLICY_BDK = "BDK";
	public static $POLICY_ADMINS = "Admins";
	public static $POLICY_ALLE = "Alle";
	public static $POLICY_EINGELOGGTE = "Eingeloggte";

	public static $POLICIES = array(
        "BDK"         => "PolicyAntraegeBDK",
		"ByLDK"       => "PolicyAntraegeByLDK",
		"HeLMB"       => "PolicyAntraegeHeLMV",
		"Admins"      => "PolicyAntraegeAdmins",
		"Alle"        => "PolicyAntraegeAlle",
		"Eingeloggte" => "PolicyAntraegeEingeloggte",
	);

	/** @var null|Veranstaltung */
	protected $veranstaltung = null;

	/**
	 * @param Veranstaltung $veranstaltung
	 */
	public function __construct($veranstaltung)
	{
		$this->veranstaltung = $veranstaltung;
	}


	/**
	 * @static
	 * @abstract
	 * @return string
	 */
	static public function getPolicyID()
	{
		return "";
	}

	/**
	 * @static
	 * @abstract
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "";
	}


	/**
	 * @static
	 * @abstract
	 * @return bool
	 */
	abstract public function checkCurUserHeuristically();

	/**
	 * @abstract
	 * @return string
	 */
	abstract public function getOnCreateDescription();


	/**
	 * @return string
	 */
	public function getAntragstellerInView()
	{
		return "antragstellerIn_std";
	}

	public function isValidName($name)
	{
		return (trim($name) != "");
	}


	protected function getSubmitPerson($andereAntragstellerInErlaubt)
	{
		if (Yii::app()->user->isGuest) {
			$antragstellerIn = null;
		} elseif ($andereAntragstellerInErlaubt && isset($_REQUEST["andere_antragstellerIn"])) {
			$antragstellerIn = null;
		} else {
			/** @var Person $antragstellerIn */
			$antragstellerIn = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			if ($antragstellerIn) {
				$name_aenderung = (isset($_REQUEST["Person"]["name"]) && $antragstellerIn->name !== $_REQUEST["Person"]["name"]);
				$orga_aenderung = (isset($_REQUEST["Person"]["organisation"]) && $antragstellerIn->organisation !== $_REQUEST["Person"]["organisation"]);
				if ($name_aenderung || $orga_aenderung) {
					$antragstellerIn->name = $_REQUEST["Person"]["name"];
					if (isset($_REQUEST["Person"]["organisation"])) {
						$antragstellerIn->organisation = $_REQUEST["Person"]["organisation"];
					}
					$antragstellerIn->save();
				}
			}
		}

		if ($antragstellerIn === null && isset($_REQUEST["Person"])) {
			$antragstellerIn = Person::model()->findByAttributes(array("typ" => Person::$TYP_PERSON, "name" => trim($_REQUEST["Person"]["name"]), "status" => Person::$STATUS_UNCONFIRMED));
			if (!$antragstellerIn) {
				$antragstellerIn                 = new Person();
				$antragstellerIn->attributes     = $_REQUEST["Person"];
				$antragstellerIn->typ            = (isset($_REQUEST["Person"]["typ"]) && $_REQUEST["Person"]["typ"] == "organisation" ? Person::$TYP_ORGANISATION : Person::$TYP_PERSON);
				$antragstellerIn->admin          = 0;
				$antragstellerIn->angelegt_datum = new CDbExpression('NOW()');
				$antragstellerIn->status         = Person::$STATUS_UNCONFIRMED;
				$antragstellerIn->save();
			}
		}
		return $antragstellerIn;
	}


	/**
	 * @return bool
	 */
	public function checkAntragSubmit()
	{
		if (isset($_REQUEST["Person"])) return true;
		else return false;
	}

	/**
	 * @param Antrag $antrag
	 * @throws Exception
	 */
	public function submitAntragsstellerInView_Antrag(&$antrag)
	{
		$antragstellerIn = $this->getSubmitPerson($antrag->veranstaltung->isAdminCurUser());
		if ($antragstellerIn === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                     = new AntragUnterstuetzerInnen();
		$init->antrag_id          = $antrag->id;
		$init->rolle              = AntragUnterstuetzerInnen::$ROLLE_INITIATORIN;
		$init->unterstuetzerIn_id = $antragstellerIn->id;
		$init->position           = 0;
		$init->save();

		if (isset($_REQUEST["UnterstuetzerInnen_name"]) && is_array($_REQUEST["UnterstuetzerInnen_name"])) foreach ($_REQUEST["UnterstuetzerInnen_name"] as $i => $name) {
			if (!$this->isValidName($name)) continue;

			$name                   = trim($name);
			$person                 = new Person;
			$person->name           = $name;
			$person->typ            = Person::$TYP_PERSON;
			$person->status         = Person::$STATUS_UNCONFIRMED;
			$person->angelegt_datum = new CDbExpression('NOW()');
			$person->admin          = 0;
			if (isset($_REQUEST["UnterstuetzerInnen_organisation"]) && isset($_REQUEST["UnterstuetzerInnen_organisation"][$i])) {
				$person->organisation = $_REQUEST["UnterstuetzerInnen_organisation"][$i];
			}
			if ($person->save()) {
				$unt                     = new AntragUnterstuetzerInnen();
				$unt->antrag_id          = $antrag->id;
				$unt->unterstuetzerIn_id = $person->id;
				$unt->rolle              = AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN;
				$unt->position           = $i;
				$unt->save();
			}
		}
	}


	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		if (isset($_REQUEST["Person"])) return true;
		else return false;
	}

	/**
	 * @param Aenderungsantrag $aenderungsantrag
	 * @throws Exception
	 */
	public function submitAntragsstellerInView_Aenderungsantrag(&$aenderungsantrag)
	{
		$antragstellerIn = $this->getSubmitPerson($aenderungsantrag->antrag->veranstaltung->isAdminCurUser());
		if ($antragstellerIn === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                      = new AenderungsantragUnterstuetzerInnen();
		$init->aenderungsantrag_id = $aenderungsantrag->id;
		$init->rolle               = AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN;
		$init->unterstuetzerIn_id  = $antragstellerIn->id;
		$init->position            = 0;
		$init->save();

		if (isset($_REQUEST["UnterstuetzerInnen_name"]) && is_array($_REQUEST["UnterstuetzerInnen_name"])) foreach ($_REQUEST["UnterstuetzerInnen_name"] as $i => $name) {
			$name = trim($name);
			if (!$this->isValidName($name)) continue;

			$person                 = new Person;
			$person->name           = $name;
			$person->typ            = Person::$TYP_PERSON;
			$person->status         = Person::$STATUS_UNCONFIRMED;
			$person->angelegt_datum = new CDbExpression('NOW()');
			$person->admin          = 0;
			if (isset($_REQUEST["UnterstuetzerInnen_orga"]) && isset($_REQUEST["UnterstuetzerInnen_orga"][$i])) {
				$person->organisation = $_REQUEST["UnterstuetzerInnen_orga"][$i];
			}
			if ($person->save()) {
				$unt                      = new AenderungsantragUnterstuetzerInnen();
				$unt->aenderungsantrag_id = $aenderungsantrag->id;
				$unt->unterstuetzerIn_id  = $person->id;
				$unt->rolle               = AenderungsantragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN;
				$unt->position            = $i;
				$unt->save();
			}
		}
	}

	/**
	 * @abstract
	 * @return string
	 */
	abstract public function getPermissionDeniedMsg();


	/**
	 * @static
	 * @param string $id
	 * @param Veranstaltung $veranstaltung
	 * @throws Exception
	 * @return IPolicyAntraege
	 */
	public static function getInstanceByID($id, &$veranstaltung)
	{
		/** @var IPolicyAntraege $polClass */
		foreach (static::$POLICIES as $polId => $polClass) if ($polId == $id) return new $polClass($veranstaltung);
		throw new Exception("Unbekannte Policy: " . $id);
	}


	/**
	 * @static
	 * @return array
	 */
	public static function getAllInstances()
	{
		$arr = array();
		/** @var IPolicyAntraege $polClass */
		foreach (static::$POLICIES as $polId => $polClass) $arr[$polId] = $polClass::getPolicyName();
		return $arr;
	}

}
