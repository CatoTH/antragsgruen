<?php

class PolicyUnterstuetzenNiemand extends IPolicyUnterstuetzen {


	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Niemand";
	}

	/**
	 * @static
	 * @return bool
	 */
	public function checkCurUserHeuristically()
	{
		return false;
	}



	/**
	 * @return bool
	 */
	public function checkAntragSubmit()
	{
		return false;
	}


	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		return false;
	}

	/**
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		return false;
	}
}