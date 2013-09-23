<?php

Yii::import("application.models.policies.*");

abstract class IPolicyAntraege
{

	public static $POLICY_BAYERN_LDK = "ByLDK";
	public static $POLICY_ADMINS = "Admins";
	public static $POLICY_ALLE = "Alle";
	public static $POLICY_EINGELOGGTE = "Eingeloggte";

	public static $POLICIES = array(
		"ByLDK"       => "PolicyAntraegeByLDK",
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


	protected function getSubmitPerson()
	{
		if (Yii::app()->user->isGuest) {
			$antragstellerIn = null;
		} else {
			$antragstellerIn = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
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
		$antragstellerIn = $this->getSubmitPerson();
		if ($antragstellerIn === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                   = new AntragUnterstuetzerInnen();
		$init->antrag_id        = $antrag->id;
		$init->rolle            = AntragUnterstuetzerInnen::$ROLLE_INITIATORIN;
		$init->unterstuetzerIn_id = $antragstellerIn->id;
		$init->position         = 0;
		$init->save();

		if (isset($_REQUEST["UnterstuetzerInnen"]) && is_array($_REQUEST["UnterstuetzerInnen"])) foreach ($_REQUEST["UnterstuetzerInnen"] as $i => $name) {
			$name = trim($name);
			if ($name != "") {
				$person                 = new Person;
				$person->name           = $name;
				$person->typ            = Person::$TYP_PERSON;
				$person->status         = Person::$STATUS_UNCONFIRMED;
				$person->angelegt_datum = "NOW()";
				$person->admin          = 0;
				if ($person->save()) {
					$unt                   = new AntragUnterstuetzerInnen();
					$unt->antrag_id        = $antrag->id;
					$unt->unterstuetzerIn_id = $person->id;
					$unt->rolle            = AntragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN;
					$unt->position         = $i;
					$unt->save();
				}
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
		$antragstellerIn = $this->getSubmitPerson();
		if ($antragstellerIn === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                      = new AenderungsantragUnterstuetzerInnen();
		$init->aenderungsantrag_id = $aenderungsantrag->id;
		$init->rolle               = AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN;
		$init->unterstuetzerIn_id    = $antragstellerIn->id;
		$init->position            = 0;
		$init->save();

		if (isset($_REQUEST["UnterstuetzerInnen"]) && is_array($_REQUEST["UnterstuetzerInnen"])) foreach ($_REQUEST["UnterstuetzerInnen"] as $i => $name) {
			$name = trim($name);
			if ($name != "") {
				$person                 = new Person;
				$person->name           = $name;
				$person->typ            = Person::$TYP_PERSON;
				$person->status         = Person::$STATUS_UNCONFIRMED;
				$person->angelegt_datum = "NOW()";
				$person->admin          = 0;
				if ($person->save()) {
					$unt                      = new AenderungsantragUnterstuetzerInnen();
					$unt->aenderungsantrag_id = $aenderungsantrag->id;
					$unt->unterstuetzerIn_id    = $person->id;
					$unt->rolle               = AenderungsantragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN;
					$unt->position            = $i;
					$unt->save();
				}
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
		throw new Exception("Unbekannte Policy");
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
