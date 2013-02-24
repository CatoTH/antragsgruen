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
		return "Organisation, Delegierte, oder 15 Mitglieder";
	}


	/**
	 * @return bool
	 */
	public function checkCurUserHeuristically()
	{
		return !$this->checkAntragsschlussVorbei(); // Jeder darf, auch nicht Eingeloggte
	}

	/**
	 * @abstract
	 * @return string
	 */
	public function getPermissionDeniedMsg()
	{
		return "";
	}

	/**
	 * @return string
	 */
	public function getAntragstellerInView()
	{
		return "antragstellerin_delegiert_orga_15";
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
		if ($this->checkAntragsschlussVorbei()) return false;
		if (!isset($_REQUEST["Person"]) || !isset($_REQUEST["Person"]["typ"])) return false;
		if (!$this->isValidName($_REQUEST["Person"]["name"])) return false;

		switch ($_REQUEST["Person"]["typ"]) {
			case "delegiert":
				return true;
				break;
			case "mitglied":
				if (!isset($_REQUEST["UnterstuetzerInnen"]) || count($_REQUEST["UnterstuetzerInnen"]) < 14) return false;
				$incorrect = false;
				foreach ($_REQUEST["UnterstuetzerInnen"] as $unters) if (!$this->isValidName($unters)) $incorrect = true;
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
	 * @param Antrag $antrag
	 * @throws Exception
	 */
	public function submitAntragsstellerInView_Antrag(&$antrag)
	{
		parent::submitAntragsstellerInView_Antrag($antrag);

		foreach ($_REQUEST["UnterstuetzerInnen"] as $unterstuetzer) {
			$person                 = new Person();
			$person->admin          = 0;
			$person->name           = trim($unterstuetzer);
			$person->typ            = Person::$TYP_PERSON;
			$person->status         = Person::$STATUS_UNCONFIRMED;
			$person->email          = "";
			$person->angelegt_datum = date("Y-m-d H:i:s");
			$person->save();

			$init                   = new AntragUnterstuetzer();
			$init->antrag_id        = $antrag->id;
			$init->rolle            = AntragUnterstuetzer::$ROLLE_UNTERSTUETZER;
			$init->unterstuetzer_id = $person->id;
			$init->save();
		}
	}


	/**
	 * @param Aenderungsantrag $aenderungsantrag
	 * @throws Exception
	 */
	public function submitAntragsstellerInView_Aenderungsantrag(&$aenderungsantrag)
	{
		parent::submitAntragsstellerInView_Aenderungsantrag($aenderungsantrag);

		foreach ($_REQUEST["UnterstuetzerInnen"] as $unterstuetzer) {
			$person                 = new Person();
			$person->admin          = 0;
			$person->name           = trim($unterstuetzer);
			$person->typ            = Person::$TYP_PERSON;
			$person->status         = Person::$STATUS_UNCONFIRMED;
			$person->email          = "";
			$person->angelegt_datum = date("Y-m-d H:i:s");
			$person->save();

			$init                      = new AenderungsantragUnterstuetzer();
			$init->aenderungsantrag_id = $aenderungsantrag->id;
			$init->rolle               = AenderungsantragUnterstuetzer::$ROLLE_UNTERSTUETZER;
			$init->unterstuetzer_id    = $person->id;
			$init->save();
		}
	}

	/*	public function checkOnCreate($antrag, $antragstellerin, $unterstuetzerinnen)
		{
			if ($this->checkAntragsschlussVorbei()) return false;

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
	*/
	/**
	 * @return string
	 */
	public function getOnCreateDescription()
	{
		return "Mindestens 15 UnterstützerInnen (oder min. eine Organisation)";
	}
}
