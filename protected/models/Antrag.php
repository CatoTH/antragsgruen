<?php

Yii::import('application.models._base.BaseAntrag');

class Antrag extends BaseAntrag
{
	public static $TYP_ANTRAG = 0;
	public static $TYP_SATZUNG = 1;
	public static $TYP_RESOLUTION = 2;
	public static $TYP_INITIATIVANTRAG = 3;
	public static $TYP_GO = 4;
	public static $TYP_FINANZANTRAG = 5;
	public static $TYP_WAHLPROGRAMM = 6;
	public static $TYPEN = array(
		0 => "Antrag",
		1 => "Satzung",
		2 => "Resolution",
		3 => "Initiativantrag",
		4 => "GO-Antrag",
		5 => "Finanzantrag",
		6 => "Wahlprogramm",
	);

	public static $TYP_PREFIX = array(
		0 => "A",
		1 => "S",
		2 => "R",
		3 => "I",
		4 => "GO",
		5 => "F",
		6 => "Kapitel ",
	);

	private $absaetze = null;

	/**
	 * @return array|string[]
	 */
	public function attributeLabels()
	{
		$val                   = parent::attributeLabels();
		$val['abgeleitet_von'] = "Abgeleitet von";
		$val['abonnenten']     = "AbonnentInnen";
		$val['antraege']       = "Löst ab";
		return $val;
	}

	/**
	 * @var $className string
	 * @return Antrag
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return array|array[]
	 */
	public function rules()
	{
		$rules     = parent::rules();
		$rules_neu = array();
		foreach ($rules as $rule) if ($rule[1] == "required") {
			$fields = array();
			$x      = explode(",", $rule[0]);
			foreach ($x as $y) if (!in_array(trim($y), array("status_string", "revision_name"))) $fields[] = trim($y);
			if (!in_array("typ", $fields)) $fields[] = "typ";
			if (count($fields) > 0) {
				$rule[0]     = implode(", ", $fields);
				$rules_neu[] = $rule;
			}
		} else $rules_neu[] = $rule;

		return $rules_neu;
	}


	/**
	 * @param bool $nurfreigeschaltete
	 * @param bool $praesentations_hacks
	 * @return array|AntragAbsatz[]
	 */
	public function getParagraphs($nurfreigeschaltete = true, $praesentations_hacks = false)
	{
		if (!is_null($this->absaetze)) return $this->absaetze;
		$this->absaetze = array();
		if ($nurfreigeschaltete) {
			$aenders = array();
			foreach ($this->aenderungsantraege as $ant) if (!in_array($ant->status, IAntrag::$STATI_UNSICHTBAR)) $aenders[] = $ant;
		} else {
			$aenders = $this->aenderungsantraege;
		}
		$komms = $this->antragKommentare;

		HtmlBBcodeUtils::initZeilenCounter();
		$arr = HtmlBBcodeUtils::bbcode2html_absaetze(trim($this->text), $praesentations_hacks);
		for ($i = 0; $i < count($arr["html"]); $i++) {
			$html_plain       = HtmlBBcodeUtils::wrapWithTextClass($arr["html_plain"][$i]);
			$this->absaetze[] = new AntragAbsatz($arr["html"][$i], $html_plain, $arr["bbcode"][$i], $this->id, $i, $komms, $aenders);
		}
		return $this->absaetze;
	}


	/**
	 * @return bool
	 */
	public function binInitiatorIn()
	{
		$person_id = Yii::app()->user->getState("person_id");
		if (is_null($person_id)) return false;

		foreach ($this->antragUnterstuetzer as $u) {
			/** @var AntragUnterstuetzer $u */
			if ($u->rolle == AntragUnterstuetzer::$ROLLE_INITIATOR && $u->unterstuetzer->id == $person_id) return true;
		}
		return false;
	}


	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|Antrag[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 5)
	{
		$oCriteria        = new CDbCriteria();
		$oCriteria->alias = "antrag";
		if ($veranstaltung_id > 0) $oCriteria->addCondition("antrag.veranstaltung = " . IntVal($veranstaltung_id));
		$oCriteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->order = 'antrag.datum_einreichung DESC';
		$dataProvider     = new CActiveDataProvider('Antrag', array(
			'criteria'   => $oCriteria,
			'pagination' => array(
				'pageSize' => IntVal($limit),
			),
		));
		return $dataProvider->data;
	}

	/**
	 * @return int
	 */
	public function getMaxAenderungsRevNr() {
		$max_rev = 0;
		$andereantrs = $this->aenderungsantraege;
		foreach ($andereantrs as $antr) {
			// Etwas messy, wg. "Ä" und UTF-8. Alternative Implementierung: auf mbstring.func_overload testen und entsprechend vorgehen
			$index = -1;
			for ($i = 0; $i < strlen($antr->revision_name) && $index == -1; $i++) {
				if (is_numeric(substr($antr->revision_name, $i, 1))) $index = $i;
			}
			$revs  = substr($antr->revision_name, $index);
			$revnr = IntVal($revs);
			if ($revnr > $max_rev) $max_rev = $revnr;
		}
		return $max_rev;
	}


	/**
	 * @return string
	 */
	public function naechsteAenderungsRevNr()
	{
		$max_rev = 0;
		if ($this->veranstaltung0->ae_nummerierung_global) {
			$antraege = $this->veranstaltung0->antraege;
			foreach ($antraege as $ant) {
				$m = $ant->getMaxAenderungsRevNr();
				if ($m > $max_rev) $max_rev = $m;
			}
		} else {
			$max_rev = $this->getMaxAenderungsRevNr();
		}
		return "Ä" . ($max_rev + 1);
	}


	/**
	 * @return string
	 */
	public function nameMitRev()
	{
		$name = $this->revision_name;
		if (strlen($this->revision_name) > 1 && !in_array($this->revision_name[strlen($this->revision_name - 1)], array(":", "."))) $name .= ":";
		$name .= " " . $this->name;
		return $name;
	}


	/**
	 * @param string $suchbegriff
	 * @return array|Antrag[]
	 */
	public static function suche($suchbegriff)
	{
		return Antrag::model()->findAll("(`text` LIKE '%" . addslashes($suchbegriff) . "%' OR `begruendung` LIKE '%" . addslashes($suchbegriff) . "%') AND status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")");
	}

	/*
	public function getRelationLabel($relationName, $n = null, $useRelationLabel = true) {
		if ($relationName == "abonnenten") return Yii::t('app', ($n == 1 ? 'AbonnentIn' : 'AbonnentInnen'));
		if ($relationName == "antraege") return "Wurde abgelöst von";
		return parent::getRelationLabel($relationName, $n, $useRelationLabel);
	}
	*/

	/**
	 * @param $attribute
	 * @param $params
	 */
	public function checkUnterstuetzer($attribute, $params)
	{
		var_dump($params);
		echo "!";
	}


}