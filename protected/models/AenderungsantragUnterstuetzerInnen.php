<?php

/**
 * @property integer $aenderungsantrag_id
 * @property integer $unterstuetzerIn_id
 * @property string $rolle
 * @property string $kommentar
 * @property integer $position
 *
 * @property Person $person
 * @property Aenderungsantrag $aenderungsantrag
 */

class AenderungsantragUnterstuetzerInnen extends IUnterstuetzerInnen
{

    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}


	public function tableName() {
		return 'aenderungsantrag_unterstuetzerInnen';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'ÄnderungsantragUnterstützerIn|ÄnderungsantragUnterstützerInnen', $n);
	}

	public static function representingColumn() {
		return 'rolle';
	}

	public function rules() {
		return array(
			array('aenderungsantrag_id, unterstuetzerIn_id, rolle', 'required'),
			array('aenderungsantrag_id, unterstuetzerIn_id, position', 'numerical', 'integerOnly'=>true),
			array('rolle', 'length', 'max'=>13),
			array('kommentar', 'safe'),
			array('kommentar', 'default', 'setOnEmpty' => true, 'value' => null),
			array('aenderungsantrag_id, unterstuetzerIn_id, rolle, kommentar, position', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'person' => array(self::BELONGS_TO, 'Person', 'unterstuetzerIn_id'),
			'aenderungsantrag' => array(self::BELONGS_TO, 'Aenderungsantrag', 'aenderungsantrag_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'aenderungsantrag_id' => null,
			'unterstuetzerIn_id' => null,
			'rolle' => Yii::t('app', 'Rolle'),
			'kommentar' => Yii::t('app', 'Kommentar'),
			'person' => null,
			'aenderungsantrag' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('aenderungsantrag_id', $this->aenderungsantrag_id);
		$criteria->compare('unterstuetzerIn_id', $this->unterstuetzerIn_id);
		$criteria->compare('rolle', $this->rolle, true);
		$criteria->compare('kommentar', $this->kommentar, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}