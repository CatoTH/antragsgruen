<?php

/**
 * @property integer $id
 * @property string $name
 * @property string $name_kurz
 * @property string $antrag_einleitung
 * @property string $datum_von
 * @property string $datum_bis
 * @property string $antragsschluss
 * @property string $policy_antraege
 * @property string $policy_aenderungsantraege
 * @property string $policy_kommentare
 * @property integer $typ
 * @property string $admin_email
 * @property integer $freischaltung_antraege
 * @property integer $freischaltung_aenderungsantraege
 * @property integer $freischaltung_kommentare
 *
 * @property Antrag[] $antraege
 * @property Person[] $abonnenten
 * @property Texte[] $texte
 */
abstract class BaseVeranstaltung extends GxActiveRecord
{

	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'veranstaltung';
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'Veranstaltung|Veranstaltungen', $n);
	}

	public static function representingColumn()
	{
		return 'name';
	}

	public function rules()
	{
		return array(
			array('name, freischaltung_antraege, freischaltung_aenderungsantraege, freischaltung_kommentare, policy_antraege, policy_aenderungsantraege, policy_kommentare, typ', 'required'),
			array('name', 'length', 'max' => 200),
			array('name_kurz', 'length', 'max' => 45),
			array('antragsschluss, admin_email', 'safe'),
			array('antragsschluss', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, freischaltung_antraege, name_kurz, antrag_einleitung, datum_von, datum_bis, antragsschluss, policy_antraege, policy_aenderungsantraege, policy_kommentare, typ', 'safe', 'on' => 'search'),
		);
	}

	public function relations()
	{
		return array(
			'antraege'   => array(self::HAS_MANY, 'Antrag', 'veranstaltung'),
			'abonnenten' => array(self::MANY_MANY, 'Person', 'veranstaltung_abo(veranstaltung_id, abonnent_id)'),
			'texte'      => array(self::HAS_MANY, 'Texte', 'veranstaltung_id'),
		);
	}

	public function pivotModels()
	{
		return array(
			'abonnenten' => 'VeranstaltungAbo',
		);
	}

	public function attributeLabels()
	{
		return array(
			'id'                               => Yii::t('app', 'ID'),
			'name'                             => Yii::t('app', 'Name'),
			'name_kurz'                        => Yii::t('app', 'Name Kurz'),
			'antrag_einleitung'                => Yii::t('app', 'Antrag Einleitung'),
			'datum_von'                        => Yii::t('app', 'Datum Von'),
			'datum_bis'                        => Yii::t('app', 'Datum Bis'),
			'antragsschluss'                   => Yii::t('app', 'Antragsschluss'),
			'policy_antraege'                  => Yii::t('app', 'Policy Antraege'),
			'policy_aenderungsantraege'        => Yii::t('app', 'Policy Aenderungsantraege'),
			'policy_kommentare'                => Yii::t('app', 'Policy Kommentare'),
			'typ'                              => Yii::t('app', 'Typ'),
			'admin_email'                      => Yii::t('app', 'E-Mail des Admins'),
			'freischaltung_antraege'           => Yii::t('app', 'Freischaltung von Anträgen'),
			'freischaltung_aenderungsantraege' => Yii::t('app', 'Freischaltung von Änderungsanträgen'),
			'freischaltung_kommentare'         => Yii::t('app', 'Freischaltung von Kommentaren'),
			'antraege'                         => null,
			'abonnenten'                       => null,
			'texte'                            => null,
		);
	}

	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('name_kurz', $this->name_kurz, true);
		$criteria->compare('antrag_einleitung', $this->antrag_einleitung, true);
		$criteria->compare('datum_von', $this->datum_von, true);
		$criteria->compare('datum_bis', $this->datum_bis, true);
		$criteria->compare('antragsschluss', $this->antragsschluss, true);
		$criteria->compare('policy_antraege', $this->policy_antraege);
		$criteria->compare('policy_aenderungsantraege', $this->policy_aenderungsantraege);
		$criteria->compare('policy_kommentare', $this->policy_kommentare);
		$criteria->compare('typ', $this->typ);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}