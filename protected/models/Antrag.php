<?php

/**
 * @property integer $id
 * @property integer $veranstaltung_id
 * @property integer $abgeleitet_von
 * @property integer $typ
 * @property string $name
 * @property string $revision_name
 * @property string $datum_einreichung
 * @property string $datum_beschluss
 * @property string $text
 * @property string $begruendung
 * @property integer $status
 * @property string $status_string
 * @property integer $cache_anzahl_zeilen
 * @property integer $cache_anzahl_absaetze
 *
 * @property Aenderungsantrag[] $aenderungsantraege
 * @property Veranstaltung $veranstaltung
 * @property Antrag $abgeleitetVon
 * @property Antrag[] $antraege
 * @property AntragKommentar[] $antragKommentare
 * @property AntragUnterstuetzerInnen[] $antragUnterstuetzerInnen
 * @property Person[] $abonnentent
 */

class Antrag extends IAntrag
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
		7 => "Dringlichkeitsantrag"
	);

	public static $TYP_PREFIX = array(
		0 => "A",
		1 => "S",
		2 => "R",
		3 => "I",
		4 => "GO",
		5 => "F",
		6 => "Kapitel ",
		7 => "D"
	);

	private $absaetze = null;

	/**
	 * @var string $className
	 * @return Antrag
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	/**
	 * @return string
	 */
	public function tableName() {
		return 'antrag';
	}

	/**
	 * @param int $n
	 * @return string
	 */
	public static function label($n = 1) {
		return Yii::t('app', 'Antrag|Antraege', $n);
	}

	/**
	 * @return string
	 */
	public static function representingColumn() {
		return 'name';
	}

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('veranstaltung_id, name, datum_einreichung, status', 'required'),
			array('veranstaltung_id, abgeleitet_von, typ, status', 'numerical', 'integerOnly'=>true),
			array('revision_name', 'length', 'max'=>50),
			array('datum_beschluss', 'length', 'max'=>45),
			array('status_string', 'length', 'max'=>55),
			array('text, begruendung', 'safe'),
			array('abgeleitet_von, typ, datum_beschluss, text, begruendung, status, status_string', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, veranstaltung_id, abgeleitet_von, typ, name, revision_name, datum_einreichung, datum_beschluss, text, begruendung, status, cache_anzahl_zeilen, cache_anzahl_, status_string', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array
	 */
	public function relations() {
		return array(
			'aenderungsantraege' => array(self::HAS_MANY, 'Aenderungsantrag', 'antrag_id'),
			'veranstaltung' => array(self::BELONGS_TO, 'Veranstaltung', 'veranstaltung_id'),
			'abgeleitetVon' => array(self::BELONGS_TO, 'Antrag', 'abgeleitet_von'),
			'antraege' => array(self::HAS_MANY, 'Antrag', 'abgeleitet_von'),
			'antragKommentare' => array(self::HAS_MANY, 'AntragKommentar', 'antrag_id'),
			'abonnenten'  => array(self::MANY_MANY, 'Person', 'antrag_abos(antrag_id, person_id)'),
			'antragUnterstuetzerInnen' => array(
				self::HAS_MANY, 'AntragUnterstuetzerInnen', 'antrag_id',
				'order' => "antragUnterstuetzerInnen.position ASC"
			),
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'veranstaltung_id' => null,
			'abgeleitet_von' => "Abgeleitet von",
			'typ' => Yii::t('app', 'Typ'),
			'name' => Yii::t('app', 'Name'),
			'revision_name' => Yii::t('app', 'Revision Name'),
			'datum_einreichung' => Yii::t('app', 'Datum Einreichung'),
			'datum_beschluss' => Yii::t('app', 'Datum Beschluss'),
			'text' => Yii::t('app', 'Text'),
			'begruendung' => Yii::t('app', 'Begruendung'),
			'status' => Yii::t('app', 'Status'),
			'status_string' => Yii::t('app', 'Status String'),
			'aenderungsantraege' => null,
			'veranstaltung' => null,
			'abgeleitetVon' => null,
			'antraege' => "Löst ab",
			'antragKommentare' => null,
			'antragUnterstuetzerInnen' => null,
			'abonnenten' => null,
		);
	}

	/**
	 * @return CActiveDataProvider
	 */
	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('veranstaltung_id', $this->veranstaltung_id);
		$criteria->compare('abgeleitet_von', $this->abgeleitet_von);
		$criteria->compare('typ', $this->typ);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('revision_name', $this->revision_name, true);
		$criteria->compare('datum_einreichung', $this->datum_einreichung, true);
		$criteria->compare('datum_beschluss', $this->datum_beschluss, true);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('begruendung', $this->begruendung, true);
		$criteria->compare('status', $this->status);
		$criteria->compare('status_string', $this->status_string, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}


	/**
	 * @return int
	 */
	public function getFirstLineNo() {
		$erste_zeile = 1;
		if ($this->veranstaltung->getEinstellungen()->zeilen_nummerierung_global) {
			$antraege = $this->veranstaltung->antraegeSortiert();
			$found = false;
			foreach ($antraege as $antraege2) foreach ($antraege2 as $antrag) if (!$found) {
				/** @var Antrag $antrag */
				if ($antrag->id == $this->id) {
					$found = true;
				} else {
					$erste_zeile += $antrag->cache_anzahl_zeilen;
				}
			}
		}
		return $erste_zeile + 1; // wg. eigener Zeile für Überschrift
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

		$erste_zeile = $this->getFirstLineNo();

		HtmlBBcodeUtils::initZeilenCounter($erste_zeile);
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

		foreach ($this->antragUnterstuetzerInnen as $u) {
			/** @var AntragUnterstuetzerInnen $u */
			if ($u->rolle == AntragUnterstuetzerInnen::$ROLLE_INITIATORIN && $u->person->id == $person_id) return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function kannUeberarbeiten() {
		if ($this->veranstaltung->isAdminCurUser()) return true;
		if ($this->veranstaltung->veranstaltungsreihe->isAdminCurUser()) return true;
		if ($this->veranstaltung->getEinstellungen()->initiatorInnen_duerfen_aendern && $this->binInitiatorIn()) return true;
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
		if ($veranstaltung_id > 0) $oCriteria->addCondition("antrag.veranstaltung_id = " . IntVal($veranstaltung_id));
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
		if ($this->veranstaltung->getEinstellungen()->ae_nummerierung_global) {
			$antraege = $this->veranstaltung->antraege;
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
		if ($this->veranstaltung->getEinstellungen()->revision_name_verstecken) return $this->name;

		$name = $this->revision_name;
		if (strlen($this->revision_name) > 1 && !in_array($this->revision_name[strlen($this->revision_name) - 1], array(":", "."))) $name .= ":";
		$name .= " " . $this->name;
		return $name;
	}


	/**
	 * @param int $veranstaltung_id
	 * @param string $suchbegriff
	 * @return array|Antrag[]
	 */
	public static function suche($veranstaltung_id, $suchbegriff)
	{
		return Antrag::model()->findAll("(`name` LIKE '%" . addslashes($suchbegriff) . "%' OR `text` LIKE '%" . addslashes($suchbegriff) . "%' OR `begruendung` LIKE '%" . addslashes($suchbegriff) . "%') AND status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND veranstaltung_id = " . IntVal($veranstaltung_id));
	}


	/**
	 * @param bool $runValidation
	 * @param null $attributes
	 * @return bool
	 */
	public function save($runValidation = true, $attributes = null) {
		HtmlBBcodeUtils::initZeilenCounter();
		list($anzahl_absaetze, $anzahl_zeilen) = HtmlBBcodeUtils::getBBCodeStats(trim($this->text));
		$this->cache_anzahl_absaetze = $anzahl_absaetze;
		$this->cache_anzahl_zeilen = $anzahl_zeilen + 1; // + Überschrift

		Yii::app()->cache->delete("pdf_" . $this->veranstaltung->id);
		Yii::app()->cache->delete("pdf_" . $this->veranstaltung->id . "_" . $this->id);

		return parent::save($runValidation, $attributes);
	}

	/**
	 * @param bool $absolute
	 * @return string
	 */
	public function getLink($absolute = false) {
		return yii::app()->getBaseUrl($absolute) . yii::app()->createUrl("antrag/anzeige", array(
			"veranstaltungsreihe_id" => $this->veranstaltung->veranstaltungsreihe->subdomain,
			"veranstaltung_id" => $this->veranstaltung->url_verzeichnis,
			"antrag_id" => $this->id));
	}

}