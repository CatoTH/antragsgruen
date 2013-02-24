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
		return $this->veranstaltung->isAdminCurUser();
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg() {
		return "";
	}


	/**
	 * @return bool
	 */
	public function checkAntragSubmit()
	{
		return $this->veranstaltung->isAdminCurUser();
	}


	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		return $this->veranstaltung->isAdminCurUser();
	}

	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Nur Admins";
	}

}
