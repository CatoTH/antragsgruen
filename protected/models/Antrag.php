<?php

Yii::import('application.models._base.BaseAntrag');

class Antrag extends BaseAntrag
{
    public static $TYP_ANTRAG = 0;
    public static $TYP_SATZUNG = 1;
    public static $TYP_RESOLUTION = 2;
    public static $TYP_INITIATIVANTRAG = 3;
    public static $TYP_GO = 4;
    public static $TYPEN = array(
        0 => "Antrag",
        1 => "Satzung",
        2 => "Resolution",
        3 => "Initiativantrag",
        4 => "GO-Antrag",
		5 => "Finanzantrag",
    );

	public static $TYP_PREFIX = array(
		0 => "A",
		1 => "S",
		2 => "R",
		3 => "I",
		4 => "GO",
		5 => "F",
	);

	private $absaetze = null;

	/**
	 * @return array|string[]
	 */
	public function attributeLabels() {
        $val = parent::attributeLabels();
        $val['abgeleitet_von'] = "Abgeleitet von";
        $val['abonnenten'] = "AbonnentInnen";
        $val['antraege'] = "Löst ab";
        return $val;
    }
    /**
     * @var $className string
     * @return Antrag
     */
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

	/**
	 * @return array|array[]
	 */
	public function rules() {
        $rules = parent::rules();
        $rules_neu = array();
        foreach ($rules as $rule) if ($rule[1] == "required") {
            $fields = array();
            $x = explode(",", $rule[0]);
            foreach ($x as $y) if (!in_array(trim($y), array("status_string", "revision_name"))) $fields[] = trim($y);
			if (!in_array("typ", $fields)) $fields[] = "typ";
            if (count($fields) > 0) {
                $rule[0] = implode(", ", $fields);
                $rules_neu[] = $rule;
            }
        } else $rules_neu[] = $rule;

        return $rules_neu;
    }


	/**
	 * return array|AntragAbsatz[]
	 */
	public function getParagraphs($nurfreigeschaltete = true) {
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
		$arr = HtmlBBcodeUtils::bbcode2html_absaetze(trim($this->text));
		for ($i = 0; $i < count($arr["html"]); $i++) $this->absaetze[] = new AntragAbsatz($arr["html"][$i], $arr["html_plain"][$i], $arr["bbcode"][$i], $this->id, $i, $komms, $aenders);
		return $this->absaetze;
	}


	/**
	 * @return bool
	 */
	public function binInitiatorIn() {
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
	public static function holeNeueste($veranstaltung_id = 0, $limit = 5) {
		$oCriteria        = new CDbCriteria();
		$oCriteria->alias = "antrag";
		if ($veranstaltung_id > 0) $oCriteria->addCondition("antrag.veranstaltung = " . IntVal($veranstaltung_id));
		$oCriteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->order = 'antrag.datum_einreichung DESC';
		$dataProvider     = new CActiveDataProvider('Antrag', array(
			'criteria'      => $oCriteria,
			'pagination'    => array(
				'pageSize'      => IntVal($limit),
			),
		));
		return $dataProvider->data;
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
	public function checkUnterstuetzer($attribute, $params) {
        var_dump($params);
        echo "!";
    }


}