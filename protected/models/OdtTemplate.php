<?php

/**
 * @property integer $id
 * @property integer $veranstaltung_id
 * @property integer $typ
 * @property string $data
 *
 * @property Veranstaltung $veranstaltung
 */
class OdtTemplate extends GxActiveRecord
{

	public static $ODT_TEMPLATE_TYP_ANTRAG = 0;
	public static $ODT_TEMPLATE_TYP_AENDERUNGSANTRAG = 1;

	/**
	 * @param string $className
	 * @return EmailLog
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'ODT-Template|ODT-Templates', $n);
	}


	public function tableName()
	{
		return 'odt_templates';
	}

	public static function representingColumn()
	{
		return 'id';
	}

	public function rules()
	{
		return array(
			array('id, typ, veranstaltung_id, data', 'required'),
			array('id, typ, veranstaltung_id', 'numerical', 'integerOnly' => true),
			array('data', 'safe'),
		);
	}

	public function relations()
	{
		return array(
			'veranstaltung' => array(self::BELONGS_TO, 'Veranstaltung', 'veranstaltung_id'),
		);
	}

	public function pivotModels()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id'               => 'ID',
			'veranstaltung_id' => 'Veranstaltung-ID',
			'typ'              => 'Typ',
			'data'             => 'Text',
		);
	}
}
