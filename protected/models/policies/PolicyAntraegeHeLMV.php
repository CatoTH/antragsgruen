<?php

class PolicyAntraegeHeLMV extends IPolicyAntraege
{


	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 5;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "Gremium/LAG, oder 5 Mitglieder";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically()
	{
		return !$this->veranstaltung->checkAntragsschlussVorbei(); // Jede darf, auch nicht Eingeloggte
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return "Antragsschluss vorbei.";
		return "";
	}

	/**
	 * @return string
	 */
	public function getAntragstellerInView()
	{
		return "antragstellerIn_orga_5";
	}


	private function isValidName($name)
	{
		return (trim($name) != "");
	}


	/**
	 * @return bool
	 */
	private function checkSubmit_internal()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return false;
		if (!isset($_REQUEST["Person"]) || !isset($_REQUEST["Person"]["typ"])) return false;
		if (!$this->isValidName($_REQUEST["Person"]["name"])) return false;

		switch ($_REQUEST["Person"]["typ"]) {
			case "mitglied":
				if (!isset($_REQUEST["UnterstuetzerInnen_name"]) || count($_REQUEST["UnterstuetzerInnen_name"]) < 4) return false;
				$incorrect = false;
				foreach ($_REQUEST["UnterstuetzerInnen_name"] as $unters) if (!$this->isValidName($unters)) $incorrect = true;
				return !$incorrect;
			case "organisation":
				return true;
				break;
			default:
				return false;
		}

	}

	/**
	 * @return bool
	 */
	public function checkAenderungsantragSubmit()
	{
		return $this->checkSubmit_internal();
	}


	/**
	 * @return bool
	 */
	public function checkAntragSubmit()
	{
		return $this->checkSubmit_internal();
	}


	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Mindestens 5 UnterstützerInnen (oder min. eine Gremium, LAG...)";
	}
}
