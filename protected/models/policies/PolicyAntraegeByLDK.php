<?php

class PolicyAntraegeByLDK extends IPolicyAntraege
{


	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 1;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Organisation, Delegierte, oder 15 Mitglieder";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically() {
		return true; // Jeder darf, auch nicht Eingeloggte
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg() {
		return "";
	}

	/**
	 * @return string
	 */
	public function getAntragsstellerInView()
	{
		return "antragsstellerin_delegiert_orga_15";
	}

	/**
	 * @param Antrag $antrag
	 * @param AntragUnterstuetzer $antragstellerin
	 * @param array|AntragUnterstuetzer[] $unterstuetzerinnen
	 * @return bool
	 */
	public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen)
	{
		$num_natuerlich = 0;
		$num_juristisch = 0;
		if ($antragstellerin->unterstuetzer->typ == Person::$TYP_ORGANISATION) $num_juristisch++;
		if ($antragstellerin->unterstuetzer->typ == Person::$TYP_PERSON) $num_natuerlich++;
		foreach ($unterstuetzerinnen as $unter) {
			if ($unter->unterstuetzer->typ == Person::$TYP_ORGANISATION) $num_juristisch++;
			if ($unter->unterstuetzer->typ == Person::$TYP_PERSON) $num_natuerlich++;
		}
		if ($num_juristisch > 0) return true;
		return ($num_natuerlich >= 15);
	}

	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Mindestens 15 UnterstützerInnen (oder min. eine Organisation)";
	}
}
