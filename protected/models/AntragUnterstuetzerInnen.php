<?php

/**
 * @property integer $antrag_id
 * @property integer $unterstuetzerIn_id
 * @property string $rolle
 * @property string $kommentar
 * @property integer $position
 *
 * @property Antrag $antrag
 * @property Person $person
 */

class AntragUnterstuetzerInnen extends IUnterstuetzerInnen
{

     /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'antrag_unterstuetzerInnen';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'AntragsunterstützerIn|AntragsunterstützerInnen', $n);
	}

	public static function representingColumn() {
		return 'rolle';
	}

	public function rules() {
		return array(
			array('antrag_id, unterstuetzerIn_id, rolle', 'required'),
			array('antrag_id, unterstuetzerIn_id, position', 'numerical', 'integerOnly'=>true),
			array('rolle', 'length', 'max'=>12),
			array('kommentar', 'safe'),
			array('kommentar', 'default', 'setOnEmpty' => true, 'value' => null),
			array('antrag_id, unterstuetzerIn_id, rolle, kommentar, position', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'antrag' => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
			'person' => array(self::BELONGS_TO, 'Person', 'unterstuetzerIn_id', "order" => "`person`.`name` ASC"),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'antrag_id' => null,
			'unterstuetzerIn_id' => null,
			'rolle' => Yii::t('app', 'Rolle'),
			'kommentar' => Yii::t('app', 'Kommentar'),
			'antrag' => null,
			'person' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('antrag_id', $this->antrag_id);
		$criteria->compare('unterstuetzerIn_id', $this->unterstuetzerIn_id);
		$criteria->compare('rolle', $this->rolle, true);
		$criteria->compare('kommentar', $this->kommentar, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}