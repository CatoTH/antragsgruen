<?php

class PolicyAntraegeBDK extends IPolicyAntraege
{


	/**
	 * @static
	 * @return int
	 */
	static public function getPolicyID()
	{
		return 6;
	}

	/**
	 * @static
	 * @return string
	 */
	static public function getPolicyName()
	{
		return "BDK: Gremium oder 20 Delegierte";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return false;

		if ($this->veranstaltung->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) {
			return !Yii::app()->user->isGuest;
		} elseif ($this->veranstaltung->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			return !Yii::app()->user->isGuest;
		} else {
			return true;
		}
	}

	/**
	 * @return bool
	 */
	public function checkHeuristicallyAssumeLoggedIn() {
		return true;
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return "Antragsschluss vorbei.";
		if (Yii::app()->user->isGuest) return "Bitte logge dich dafür ein";
		return "";
	}

	/**
	 * @return string
	 */
	public function getAntragstellerInView()
	{
		return "antragstellerIn_orga_19_fulltext";
	}


	/**
	 * @return bool
	 */
	private function checkSubmit_internal()
	{
		if ($this->veranstaltung->checkAntragsschlussVorbei()) return false;
		if (Yii::app()->user->isGuest) return false;

		if (!isset($_REQUEST["Person"]) || !isset($_REQUEST["Person"]["typ"])) return false;
		if (!$this->isValidName($_REQUEST["Person"]["name"])) return false;

		switch ($_REQUEST["Person"]["typ"]) {
			case "mitglied":
				if (isset($_REQUEST["UnterstuetzerInnen_fulltext"]) && trim($_REQUEST["UnterstuetzerInnen_fulltext"]) != "") return true;

				if (!isset($_REQUEST["UnterstuetzerInnen_name"]) || count($_REQUEST["UnterstuetzerInnen_name"]) < 19) return false;
				$correct = 0;
				foreach ($_REQUEST["UnterstuetzerInnen_name"] as $unters) if ($this->isValidName($unters)) $correct++;
				return ($correct >= 19);
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
		return "Mindestens 20 Parteimitglieder oder ein Gremium (BAG, LDK)";
	}
}
