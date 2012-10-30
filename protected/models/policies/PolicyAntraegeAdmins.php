<?php

class PolicyAntraegeAdmins extends IPolicyAntraege
{

	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 2;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Nur Admins";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically() {
		return false;
	}

	/**
	 * @return int
	 */
	public function getStdUnterstuetzerFields()
	{
		return 0;
	}

	/**
	 * @param Antrag $antrag
	 * @param AntragUnterstuetzer $antragstellerin
	 * @param array|AntragUnterstuetzer[] $unterstuetzerinnen
	 * @return bool
	 */
	public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen)
	{
		return ($antragstellerin->unterstuetzer->admin == 1);
	}

	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Nur Admins";
	}

}
