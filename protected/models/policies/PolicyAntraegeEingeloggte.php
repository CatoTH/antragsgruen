<?php

class PolicyAntraegeEingeloggte extends IPolicyAntraege
{

	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 4;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Nur Eingeloggte";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically() {
		return !Yii::app()->user->isGuest;
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
		return "Bitte logge dich ein";
	}


	/**
	 * @param Antrag $antrag
	 * @param AntragUnterstuetzer $antragstellerin
	 * @param array|AntragUnterstuetzer[] $unterstuetzerinnen
	 * @return bool
	 */
	public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen)
	{
		return !Yii::app()->user->isGuest;
	}

	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Nur Eingeloggte";
	}

}
