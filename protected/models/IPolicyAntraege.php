<?php

Yii::import("application.models.policies.*");

abstract class IPolicyAntraege
{

	// Ich hab leider keine Ahnung, wie man hier einen eleganteren Auto-Discovery-Mechanmismus implementieren kann...
	private static $POLICIES = array(
		"ByLDK" => "PolicyAntraegeByLDK",
		"Admins" => "PolicyAntraegeAdmins",
		"Alle" => "PolicyAntraegeAlle",
		"Eingeloggte" => "PolicyAntraegeEingeloggte",
	);


	/**
	 * @static
	 * @abstract
	 * @return string
	 */
	static public function getPolicyID() { return ""; }

	/**
	 * @static
	 * @abstract
	 * @return string
	 */
	static public function getPolicyName() { return ""; }


	/**
	 * @static
	 * @abstract
	 * @return bool
	 */
	abstract public function checkCurUserHeuristically();

	/**
	 * @abstract
	 * @param Antrag $antrag
	 * @param AntragUnterstuetzer $antragstellerin
	 * @param array|AntragUnterstuetzer[] $unterstuetzerinnen
	 * @return bool
	 */
	abstract public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen);

	/**
	 * @abstract
	 * @return string
	 */
	abstract public function getOnCreateDescription();


	/**
	 * @abstract
	 * @return string
	 */
	abstract public function getAntragsstellerInView();


	/**
	 * @abstract
	 * @return string
	 */
	abstract public function getPermissionDeniedMsg();


	/**
	 * @static
	 * @param string $id
	 * @return IPolicyAntraege
	 * @throws Exception
	 */
	public static function getInstanceByID($id) {
		/** @var IPolicyAntraege $polClass */
		foreach (static::$POLICIES as $polId=>$polClass) if ($polId == $id) return new $polClass();
		throw new Exception("Unbekannte Policy");
	}


	/**
	 * @static
	 * @return array
	 */
	public static function getAllInstances() {
		$arr = array();
		/** @var IPolicyAntraege $polClass */
		foreach (static::$POLICIES as $polId=>$polClass) $arr[$polId] = $polClass::getPolicyName();
		return $arr;
	}

}
