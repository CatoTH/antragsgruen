<?php

class PolicyAntraegeAlle extends IPolicyAntraege
{

	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 3;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Alle";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically() {
		return true;
	}

	/**
	 * @return string
	 */
	public function getAntragsstellerInView()
	{
		return "antragstellerin_std";
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg() {
		return "";
	}


	/**
	 * @param Antrag $antrag
	 * @param AntragUnterstuetzer $antragstellerin
	 * @param array|AntragUnterstuetzer[] $unterstuetzerinnen
	 * @return bool
	 */
	public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen)
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Alle";
	}

}
