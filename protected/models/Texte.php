<?php

/**
 * @property integer $id
 * @property string $text_id
 * @property integer $veranstaltung_id
 * @property string $text
 * @property string $edit_datum
 *
 * @property Veranstaltung $veranstaltung
 */
class Texte extends GxActiveRecord
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Text|Texte', $n);
	}


	public function tableName() {
		return 'texte';
	}

	public static function representingColumn() {
		return 'text_id';
	}

	public function rules() {
		return array(
			array('text_id', 'required'),
			array('veranstaltung_id', 'numerical', 'integerOnly'=>true),
			array('text_id', 'length', 'max'=>20),
			array('text, edit_datum', 'safe'),
			array('veranstaltung_id, text, edit_datum', 'default', 'setOnEmpty' => true, 'value' => null),
		);
	}

	public function relations() {
		return array(
			'veranstaltung' => array(self::BELONGS_TO, 'Veranstaltung', 'veranstaltung_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'text_id' => Yii::t('app', 'Text'),
			'veranstaltung_id' => null,
			'text' => Yii::t('app', 'Text'),
			'edit_datum' => Yii::t('app', 'Edit Datum'),
			'veranstaltung' => null,
		);
	}
}