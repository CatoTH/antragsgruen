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
		return "Min. 15 (oder 1 Organisation)";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically() {
		return true; // Jeder darf, auch nicht Eingeloggte
	}

	/**
	 * @return int
	 */
	public function getStdUnterstuetzerFields()
	{
		return 15;
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
