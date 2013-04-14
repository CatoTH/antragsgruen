<?php

/**
 * @property integer $veranstaltungsreihe_id
 * @property integer $person_id
 * @property integer $antraege
 * @property integer $aenderungsantraege
 * @property integer $kommentare
 *
 * @property Veranstaltungsreihe $veranstaltungsreihe
 * @property Person $person
 */
class VeranstaltungsreihenAbo extends CActiveRecord
{

	/**
	 * @var string $className
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	public function tableName()
	{
		return 'veranstaltungsreihen_abos';
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'Veranstaltungsreihenabo|Veranstaltungsreihenabos', $n);
	}

	public static function representingColumn()
	{
		return 'veranstaltungsreihe_id';
	}

	public function rules()
	{
		return array(
			array('person_id, veranstaltungsreihe_id', 'required'),
		);
	}

	public function relations()
	{
		return array(
			'veranstaltungsreihe' => array(self::BELONGS_TO, 'Veranstaltungsreihe', 'veranstaltungsreihe_id'),
			'person'              => array(self::BELONGS_TO, 'Person', 'person_id'),
		);
	}

	public function attributeLabels()
	{
		return array(
			"veranstaltungsreihe_id" => "Veranstaltungsreihe",
			"person_id"              => "Person",
			"antraege"               => "Benachrichtigung bei neuen Anträgen",
			"aenderungsantraege"     => "Benachrichtigung bei neuen Änderungsanträgen",
			"kommentare"             => "Benachrichtigung bei neuen Kommentaren",
		);
	}
}