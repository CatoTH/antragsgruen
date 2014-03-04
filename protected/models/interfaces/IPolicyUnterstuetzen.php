<?php

Yii::import("application.models.policies.*");

abstract class IPolicyUnterstuetzen
{

	// Ich hab leider keine Ahnung, wie man hier einen eleganteren Auto-Discovery-Mechanmismus implementieren kann...
	private static $POLICIES = array(
		"Niemand" => "PolicyUnterstuetzenNiemand",
		"Eingeloggte" => "PolicyUnterstuetzenEingeloggte",
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
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "";
	}

	/**
	 * @abstract
	 * @return bool
	 */
	abstract public function checkCurUserHeuristically();

	/**
	 * @abstract
	 * @return bool
	 */
	abstract public function checkAntragSubmit();


	/**
	 * @abstract
	 * @return bool
	 */
	abstract public function checkAenderungsantragSubmit();

	/**
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		return false;
	}

	/**
	 * @static
	 * @param string $id
	 * @param Veranstaltung $veranstaltung
	 * @throws Exception
	 * @return IPolicyUnterstuetzen
	 */
	public static function getInstanceByID($id, &$veranstaltung)
	{
		if ($id == "") return new PolicyUnterstuetzenNiemand($veranstaltung);
		/** @var IPolicyUnterstuetzen $polClass */
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
		/** @var IPolicyUnterstuetzen $polClass */
		foreach (static::$POLICIES as $polId => $polClass) $arr[$polId] = $polClass::getPolicyName();
		return $arr;
	}


}