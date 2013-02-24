<?php

Yii::import("application.models.policies.*");

abstract class IPolicyAntraege
{

	// Ich hab leider keine Ahnung, wie man hier einen eleganteren Auto-Discovery-Mechanmismus implementieren kann...
	private static $POLICIES = array(
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
	 * @return bool
	 */
	public function checkAntragsschlussVorbei()
	{
		if ($this->veranstaltung->antragsschluss != "" && date("YmdHis") > str_replace(array(" ", ":", "-"), array("", "", ""), $this->veranstaltung->antragsschluss)) return true;
		return false;
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
		return "antragstellerin_std";
	}


	protected function getSubmitPerson()
	{
		if (Yii::app()->user->isGuest) {
			$antragstellerin = null;
		} else {
			$antragstellerin = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}

		if ($antragstellerin === null && isset($_REQUEST["Person"])) {
			$antragstellerin = Person::model()->findByAttributes(array("typ" => Person::$TYP_PERSON, "name" => trim($_REQUEST["Person"]["name"]), "status" => Person::$STATUS_UNCONFIRMED));
			if (!$antragstellerin) {
				$antragstellerin                 = new Person();
				$antragstellerin->attributes     = $_REQUEST["Person"];
				$antragstellerin->typ            = (isset($_REQUEST["Person"]["typ"]) && $_REQUEST["Person"]["typ"] == "organisation" ? Person::$TYP_ORGANISATION : Person::$TYP_PERSON);
				$antragstellerin->admin          = 0;
				$antragstellerin->angelegt_datum = new CDbExpression('NOW()');
				$antragstellerin->status         = Person::$STATUS_UNCONFIRMED;
				$antragstellerin->save();
			}
		}
		return $antragstellerin;
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
		$antragstellerin = $this->getSubmitPerson();
		if ($antragstellerin === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                   = new AntragUnterstuetzer();
		$init->antrag_id        = $antrag->id;
		$init->rolle            = AntragUnterstuetzer::$ROLLE_INITIATOR;
		$init->unterstuetzer_id = $antragstellerin->id;
		$init->save();
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
		$antragstellerin = $this->getSubmitPerson();
		if ($antragstellerin === null) {
			throw new Exception("Keine AntragstellerIn gefunden");
		}

		$init                      = new AenderungsantragUnterstuetzer();
		$init->aenderungsantrag_id = $aenderungsantrag->id;
		$init->rolle               = AenderungsantragUnterstuetzer::$ROLLE_INITIATOR;
		$init->unterstuetzer_id    = $antragstellerin->id;
		$init->save();
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
