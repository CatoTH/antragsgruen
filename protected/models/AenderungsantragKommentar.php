<?php

/**
 * @property integer $id
 * @property integer $verfasserIn_id
 * @property integer $aenderungsantrag_id
 * @property integer $absatz
 * @property string $text
 * @property string $datum
 * @property integer $status
 *
 * @property Aenderungsantrag $aenderungsantrag
 * @property Person $verfasserIn
 */
class AenderungsantragKommentar extends IKommentar
{
    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}


	public function tableName() {
		return 'aenderungsantrag_kommentar';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'AenderungsantragKommentar|AenderungsantragKommentare', $n);
	}

	public static function representingColumn() {
		return 'datum';
	}

	public function rules() {
		return array(
			array('text, datum', 'required'),
			array('id, verfasserIn_id, aenderungsantrag_id, absatz, status', 'numerical', 'integerOnly'=>true),
			array('verfasserIn_id, aenderungsantrag_id, absatz, text, status', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, verfasserIn_id, aenderungsantrag_id, absatz, text, datum, status', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'aenderungsantrag' => array(self::BELONGS_TO, 'Aenderungsantrag', 'aenderungsantrag_id'),
			'verfasserIn' => array(self::BELONGS_TO, 'Person', 'verfasserIn_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'verfasserIn_id' => null,
			'aenderungsantrag_id' => null,
			'absatz' => Yii::t('app', 'Absatz'),
			'text' => Yii::t('app', 'Text'),
			'datum' => Yii::t('app', 'Datum'),
			'status' => Yii::t('app', 'Status'),
			'aenderungsantrag' => null,
			'verfasserIn' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('verfasserIn_id', $this->verfasserIn_id);
		$criteria->compare('aenderungsantrag_id', $this->aenderungsantrag_id);
		$criteria->compare('absatz', $this->absatz);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('datum', $this->datum, true);
		$criteria->compare('status', $this->status);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->aenderungsantrag->antrag->veranstaltung;
	}


	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|AenderungsantragKommentar[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 0) {
		$antrag_ids = array();
		/** @var array|Antrag[] $antraege */
		$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $veranstaltung_id));
		foreach ($antraege as $a) $antrag_ids[] = $a->id;

		if (count($antrag_ids) == 0) return array();

		$condition = array(
			"order" => "datum DESC"
		);
		if ($limit > 0) $condition["limit"] = $limit;
		$arr = AenderungsantragKommentar::model()->with(array(
			"aenderungsantrag" => array(
				"condition" => "aenderungsantrag.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND aenderungsantrag.antrag_id IN (" . implode(", ", $antrag_ids) . ")"
			),
		))->findAllByAttributes(array("status" => AenderungsantragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}
}