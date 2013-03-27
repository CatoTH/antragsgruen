<?php

/**
 * @property integer $id
 * @property integer $verfasser_id
 * @property integer $antrag_id
 * @property integer $absatz
 * @property string $text
 * @property string $datum
 * @property integer $status
 *
 * @property Person $verfasser
 * @property Antrag $antrag
 * @property AntragKommentarUnterstuetzerInnen[] $unterstuetzerInnen
 */

class AntragKommentar extends IKommentar
{
    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'antrag_kommentar';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'AntragKommentar|AntragKommentare', $n);
	}

	public static function representingColumn() {
		return 'text';
	}

	public function rules() {
		return array(
			array('text, datum', 'required'),
			array('id, verfasser_id, antrag_id, absatz, status', 'numerical', 'integerOnly'=>true),
			array('verfasser_id, antrag_id, absatz, status', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, verfasser_id, antrag_id, absatz, text, datum, status', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'verfasser' => array(self::BELONGS_TO, 'Person', 'verfasser_id'),
			'antrag' => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
			'unterstuetzerInnen' => array(self::HAS_MANY, 'AntragKommentarUnterstuetzerInnen', 'antrag_kommentar_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'verfasser_id' => null,
			'antrag_id' => null,
			'absatz' => Yii::t('app', 'Absatz'),
			'text' => Yii::t('app', 'Text'),
			'datum' => Yii::t('app', 'Datum'),
			'status' => Yii::t('app', 'Status'),
			'verfasser' => null,
			'antrag' => null,
			'unterstuetzerInnen' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('verfasser_id', $this->verfasser_id);
		$criteria->compare('antrag_id', $this->antrag_id);
		$criteria->compare('absatz', $this->absatz);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('datum', $this->datum, true);
		$criteria->compare('status', $this->status);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|AntragKommentar[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 0) {
		$condition = ($limit > 0 ? array("limit" => $limit) : "");
		$arr = AntragKommentar::model()->with(array(
			"antrag" => array(
				"condition" => "antrag.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND antrag.veranstaltung_id = " . IntVal($veranstaltung_id)
			),
		))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->antrag->veranstaltung;
	}
}