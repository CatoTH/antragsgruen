<?php

class PolicyUnterstuetzenEingeloggte extends IPolicyUnterstuetzen {


	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Eingeloggte";
	}

	/**
	 * @static
	 * @return bool
	 */
	public function checkCurUserHeuristically()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function checkAntragSubmit()
	{
		return (!Yii::app()->user->isGuest);
	}


	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		return (!Yii::app()->user->isGuest);
	}

	/**
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return "Antragsschluss vorbei.";
		if (Yii::app()->user->isGuest) return "Bitte logge dich dafür ein";
		return "";
	}
}