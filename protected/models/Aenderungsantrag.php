<?php

/**
 * @property integer $id
 * @property integer $antrag_id
 * @property string $revision_name
 * @property string $name_neu
 * @property string $text_neu
 * @property string $begruendung_neu
 * @property string $aenderung_text
 * @property string $aenderung_begruendung
 * @property string $datum_einreichung
 * @property string $datum_beschluss
 * @property integer $status
 * @property string $status_string
 *
 * @property Antrag $antrag
 * @property AenderungsantragKommentar[] $aenderungsantragKommentare
 * @property AenderungsantragUnterstuetzerInnen[] $aenderungsantragUnterstuetzerInnen
 */
class Aenderungsantrag extends IAntrag
{
	private $absaetze = null;

    /**
     * @var string $clasName
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string
	 */
	public function tableName() {
		return 'aenderungsantrag';
	}

	/**
	 * @param int $n
	 * @return string
	 */
	public static function label($n = 1) {
		return Yii::t('app', 'Aenderungsantrag|Aenderungsantraege', $n);
	}

	/**
	 * @return string
	 */
	public static function representingColumn() {
		return 'text_neu';
	}

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('text_neu, aenderung_text, datum_einreichung, status, status', 'required'),
			array('antrag_id, status', 'numerical', 'integerOnly'=>true),
			array('revision_name', 'length', 'max'=>45),
			array('status_string', 'length', 'max'=>55),
			array('name_neu, datum_beschluss', 'safe'),
			array('antrag_id, revision_name, name_neu, datum_beschluss', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, antrag_id, revision_name, name_neu, text_neu, begruendung_neu, aenderung_text, aenderung_begruendung, datum_einreichung, datum_beschluss, status, status_string', 'safe', 'on'=>'search'),
		);

	}

	/**
	 * @return array
	 */
	public function relations() {
		return array(
			'antrag' => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
			'aenderungsantragKommentare' => array(self::HAS_MANY, 'AenderungsantragKommentar', 'aenderungsantrag_id'),
			'aenderungsantragUnterstuetzerInnen' => array(
				self::HAS_MANY, 'AenderungsantragUnterstuetzerInnen', 'aenderungsantrag_id',
				'order' => "aenderungsantragUnterstuetzerInnen.position ASC"
			),
		);
	}

	/**
	 * @return array
	 */
	public function pivotModels() {
		return array(
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'antrag_id' => null,
			'revision_name' => Yii::t('app', 'Revision Name'),
			'name_neu' => Yii::t('app', 'Name Neu'),
			'text_neu' => Yii::t('app', 'Text Neu'),
			'begruendung_neu' => Yii::t('app', 'Begruendung Neu'),
			'aenderung_text' => Yii::t('app', 'Aenderung Text'),
			'aenderung_begruendung' => Yii::t('app', 'Aenderung Begruendung'),
			'datum_einreichung' => Yii::t('app', 'Datum Einreichung'),
			'datum_beschluss' => Yii::t('app', 'Datum Beschluss'),
			'status' => Yii::t('app', 'Status'),
			'status_string' => Yii::t('app', 'Status String'),
			'antrag' => null,
			'aenderungsantragKommentare' => null,
			'aenderungsantragUnterstuetzerInnen' => null,
		);
	}

	/**
	 * @param $veranstaltung_id
	 * @return CActiveDataProvider
	 */
	public function search($veranstaltung_id) {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);

		if (is_null($this->antrag)) {
			$ids = array();
			/** @var Antrag[]|array $antraege */
			$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $veranstaltung_id));
			foreach ($antraege as $ant) $ids[] = $ant->id;
			$criteria->addInCondition("antrag_id", $ids);
		} else {
			$criteria->compare('antrag_id', $this->antrag_id);
		}
		$criteria->compare('revision_name', $this->revision_name, true);
		$criteria->compare('name_neu', $this->name_neu, true);
		$criteria->compare('text_neu', $this->text_neu, true);
		$criteria->compare('begruendung_neu', $this->begruendung_neu, true);
		$criteria->compare('aenderung_text', $this->aenderung_text, true);
		$criteria->compare('aenderung_begruendung', $this->aenderung_begruendung, true);
		$criteria->compare('datum_einreichung', $this->datum_einreichung, true);
		$criteria->compare('datum_beschluss', $this->datum_beschluss, true);
		$criteria->compare('status', $this->status);
		$criteria->compare('status_string', $this->status_string, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}


	/**
	 * @param bool $runValidation
	 * @param null $attributes
	 * @return bool
	 */
	public function save($runValidation = true, $attributes = null) {
		Yii::app()->cache->delete("pdf_ae_" . $this->antrag->veranstaltung->id);

		return parent::save($runValidation, $attributes);
	}


	/**
	 * @return array|int[]
	 */
	public function getAffectedParagraphs() {
		$paras = array();
		foreach ($this->getDiffParagraphs() as $i => $p) if ($p != "") $paras[] = $i;
		return $paras;
	}


	/**
	 * @param array|string[] $paragraphs
	 */
	public function setDiffParagraphs($paragraphs) {
		$this->text_neu = json_encode($paragraphs);
	}

	/**
	 * @return array
	 */
	public function getDiffParagraphs() {
		return json_decode($this->text_neu);
	}

	/**
	 *
	 */
	public function calcDiffText() {
		$text_vorher  = $this->antrag->text;
		$paragraphs = $this->antrag->getParagraphs(false, false);
		$text_neu = array();
		$diff = $this->getDiffParagraphs();
		foreach ($paragraphs as $i => $para) {
			if ($diff[$i] != "") $text_neu[] = $diff[$i];
			else $text_neu[] = $para->str_bbcode;
		}
		$diff      = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)));
		$diff_text = "";

		if ($this->name_neu != $this->antrag->name) $diff_text .= "Neuer Titel des Antrags:\n[QUOTE]" . $this->name_neu . "[/QUOTE]\n\n";
		$diff_text .= DiffUtils::diff2text($diff, $this->antrag->getFirstLineNo());

		$this->aenderung_text    = $diff_text;
	}


	/**
	 * @return array|AntragAbsatz[]
	 */
	public function getAntragstextParagraphs() {
		if (!is_null($this->absaetze)) return $this->absaetze;
		$this->absaetze = array();
		$komms = $this->aenderungsantragKommentare;

		HtmlBBcodeUtils::initZeilenCounter();
		$arr = HtmlBBcodeUtils::bbcode2html_absaetze(trim($this->aenderung_text));

		for ($i = 0; $i < count($arr["html"]); $i++) {
			$html_plain = HtmlBBcodeUtils::wrapWithTextClass($arr["html_plain"][$i]);
			$this->absaetze[] = new AntragAbsatz($arr["html"][$i], $html_plain, $arr["bbcode"][$i], $this->id, $i, $komms, array());
		}
		return $this->absaetze;
	}

	private $first_diff_line = null;
	public function getFirstDiffLine() {
		if ($this->first_diff_line !== null) return $this->first_diff_line;
		$text_vorher  = $this->antrag->text;
		$paragraphs = $this->antrag->getParagraphs(false, false);
		$text_neu = array();
		$diff = $this->getDiffParagraphs();
		foreach ($paragraphs as $i => $para) {
			if ($diff[$i] != "") $text_neu[] = $diff[$i];
			else $text_neu[] = $para->str_bbcode;
		}
		$diff      = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)));

		$this->first_diff_line = DiffUtils::getFistDiffLine($diff, $this->antrag->getFirstLineNo());
		return $this->first_diff_line;
	}

	/**
	 * @return string
	 */
	public function naechsteAenderungsRevNr()
	{
		$max_rev = 0;
		if ($this->antrag->veranstaltung->getEinstellungen()->ae_nummerierung_nach_zeile) {
			$line = $this->getFirstDiffLine();
			$ae_rev_base = $this->antrag->revision_name . "-Ä" . $line . "-";
			$max_rev = 0;
			foreach ($this->antrag->aenderungsantraege as $ae) {
				$x = explode($ae_rev_base, $ae->revision_name);
				if (count($x) == 2 && $x[1] > $max_rev) $max_rev = IntVal($x[1]);
			}
			return $ae_rev_base . ($max_rev + 1);
		} elseif ($this->antrag->veranstaltung->getEinstellungen()->ae_nummerierung_global) {
			$antraege = $this->antrag->veranstaltung->antraege;
			foreach ($antraege as $ant) {
				$m = $ant->getMaxAenderungsRevNr();
				if ($m > $max_rev) $max_rev = $m;
			}
		} else {
			$max_rev = $this->antrag->getMaxAenderungsRevNr();
		}
		return "Ä" . ($max_rev + 1);
	}

	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|Aenderungsantrag[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 5) {
		$oCriteria        = new CDbCriteria();
		$oCriteria->alias = "aenderungsantrag";
		$oCriteria->addNotInCondition("aenderungsantrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->with = "antrag";
		if ($veranstaltung_id > 0) $oCriteria->addCondition("antrag.veranstaltung_id = " . IntVal($veranstaltung_id));
		$oCriteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->order           = 'aenderungsantrag.datum_einreichung DESC';
		$dataProvider               = new CActiveDataProvider('Aenderungsantrag', array(
			'criteria'      => $oCriteria,
			'pagination'    => array(
				'pageSize'      => IntVal($limit),
			),
		));
		return $dataProvider->data;
	}

	/**
	 * @return Person[]
	 */
	public function getAntragstellerInnen() {
		$antragstellerInnen = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $antragstellerInnen[] = $relatedModel->person;
		}
		return $antragstellerInnen;
	}

	/**
	 * @return Person[]
	 */
	public function getUnterstuetzerInnen() {
		$unterstuetzerInnen = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $relatedModel->person;
		}
		return $unterstuetzerInnen;
	}

	/**
	 * @return Person[]
	 */
	public function getZustimmungen() {
		$zustimmung_von     = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG) $zustimmung_von[] = $relatedModel->person;
		}
		return $zustimmung_von;
	}

	/**
	 * @return Person[]
	 */
	public function getAblehnungen() {
		$ablehnung_von      = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG_NICHT) $ablehnung_von[] = $relatedModel->person;
		}
		return $ablehnung_von;
	}

	/**
	 * @return bool
	 */
	public function binInitiatorIn() {

		if (!Yii::app()->user->isGuest) {
			/** @var Person $current_user  */
			$current_user = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			foreach ($this->aenderungsantragUnterstuetzerInnen as $u) {
				/** @var AenderungsantragUnterstuetzerInnen $u */
				if ($u->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN && $u->person->id == $current_user->id) return true;
			}
		}
		return false;
	}

	/**
	 * @param int $veranstaltung_id
	 * @param string $suchbegriff
	 * @return array|Aenderungsantrag[]
	 */
	public static function suche($veranstaltung_id, $suchbegriff) {
		$ids = array();

		/** @var array|Antrag[] $antraege  */
		$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $veranstaltung_id));
		foreach ($antraege as $ant) $ids[] = $ant->id;
		if (count($ids) == 0) return array();

		return Aenderungsantrag::model()->findAll("(`aenderung_text` LIKE '%" . addslashes($suchbegriff) . "%' OR `aenderung_begruendung` LIKE '%" . addslashes($suchbegriff) . "%') AND status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND antrag_id IN (" . implode(", ", $ids) . ")");
	}


	/**
	 * @param bool $absolute
	 * @return string
	 */
	public function getLink($absolute = false) {
		return yii::app()->getBaseUrl($absolute) . yii::app()->createUrl("aenderungsantrag/anzeige", array(
			"veranstaltungsreihe_id" => $this->antrag->veranstaltung->veranstaltungsreihe->subdomain,
			"veranstaltung_id" => $this->antrag->veranstaltung->url_verzeichnis,
			"antrag_id" => $this->antrag_id,
			"aenderungsantrag_id" => $this->id
		));
	}

}