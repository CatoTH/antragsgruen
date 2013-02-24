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
		return !$this->checkAntragsschlussVorbei();
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
		return !$this->checkAntragsschlussVorbei();
	}


	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		return !$this->checkAntragsschlussVorbei();
	}


	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Alle";
	}

}
