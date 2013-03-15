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
 * @property string $policy_unterstuetzen
 * @property string $yii_url
 * @property integer $typ
 * @property string $admin_email
 * @property integer $freischaltung_antraege
 * @property integer $freischaltung_aenderungsantraege
 * @property integer $freischaltung_kommentare
 * @property integer $ae_nummerierung_global
 * @property integer $zeilen_nummerierung_global
 * @property integer $bestaetigungs_emails
 * @property string $logo_url
 * @property string $fb_logo_url
 * @property integer $revision_name_verstecken
 * @property integer $kommentare_unterstuetzbar
 * @property integer $ansicht_minimalistisch
 *
 * @property Antrag[] $antraege
 * @property VeranstaltungPerson[] $veranstaltung_personen
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
			array('name, freischaltung_antraege, yii_url, freischaltung_aenderungsantraege, revision_name_verstecken, kommentare_unterstuetzbar, ansicht_minimalistisch, freischaltung_kommentare, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, ae_nummerierung_global, zeilen_nummerierung_global, bestaetigungs_emails', 'required'),
			array('name, logo_url, fb_logo_url', 'length', 'max' => 200),
			array('name_kurz, yii_url', 'length', 'max' => 45),
			array('antragsschluss, antrag_einleitung, admin_email', 'safe'),
			array('antragsschluss', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, yii_url, logo_url, fb_logo_url, freischaltung_antraege, name_kurz, revision_name_verstecken, kommentare_unterstuetzbar, antrag_einleitung, datum_von, datum_bis, antragsschluss, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, ae_nummerierung_global, zeilen_nummerierung_global, bestaetigungs_emails', 'safe', 'on' => 'search'),
		);
	}

	public function relations()
	{
		return array(
			'antraege'   => array(self::HAS_MANY, 'Antrag', 'veranstaltung'),
			'veranstaltung_personen'   => array(self::HAS_MANY, 'VeranstaltungPerson', 'veranstaltung_id'),
			'texte'      => array(self::HAS_MANY, 'Texte', 'veranstaltung_id'),
		);
	}

	public function pivotModels()
	{
		return array(
			'veranstaltung_personen' => 'VeranstaltungPerson'
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
			'policy_unterstuetzen'             => Yii::t('app', 'Policy Unterstützen'),
			'typ'                              => Yii::t('app', 'Typ'),
			'admin_email'                      => Yii::t('app', 'E-Mail des Admins'),
			'freischaltung_antraege'           => Yii::t('app', 'Freischaltung von Anträgen'),
			'freischaltung_aenderungsantraege' => Yii::t('app', 'Freischaltung von Änderungsanträgen'),
			'freischaltung_kommentare'         => Yii::t('app', 'Freischaltung von Kommentaren'),
			'yii_url'                          => Yii::t('app', 'URL-Kürzel'),
			'logo_url'                         => Yii::t('app', 'Logo-URL'),
			'fb_logo_url'                      => Yii::t('app', 'Facebook-Bild URL'),
			'antraege'                         => null,
			'veranstaltung_personen'           => null,
			'texte'                            => null,
			'ae_nummerierung_global'           => Yii::t('app', 'ÄA-Nummerierung für die ganze Veranstaltung'),
			'zeilen_nummerierung_global'       => Yii::t('app', 'Zeilennummerierung durchgehend für die ganze Veranstaltung'),
			'bestaetigungs_emails'             => Yii::t('app', 'Bestätigungsmails an AntragsStellerInnen'),
			'revision_name_verstecken'         => Yii::t('app', 'Revisionsname verstecken'),
			'kommentare_unterstuetzbar'        => Yii::t('app', 'Kommentare unterstützbar'),
			'ansicht_minimalistisch'          => Yii::t('app', 'Minimalistische Ansicht'),
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
		$criteria->compare('policy_unterstuetzen', $this->policy_unterstuetzen);
		$criteria->compare('typ', $this->typ);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}