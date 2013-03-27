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
 * @property string $url_verzeichnis
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
 * @property string $einstellungen
 *
 * @property Antrag[] $antraege
 * @property Person[] $admins
 * @property Texte[] $texte
 * @property Veranstaltungsreihe $veranstaltungsreihe
 */
class Veranstaltung extends GxActiveRecord
{

	/** @var null|IPolicyAntraege */
	private $policy_antraege_obj = null;
	private $policy_aenderungsantraege_obj = null;

	/** @var null|IPolicyUnterstuetzen */
	private $policy_unterstuetzen_obj = null;

	public static $POLICY_NUR_ADMINS = 0;
	public static $POLICY_PARTEIMITGLIEDER = 1;
	public static $POLICY_REGISTRIERTE = 2;
	public static $POLICY_ALLE = 3;
	public static $POLICY_NIEMAND = 4;
	public static $POLICY_ALLE_FREISCHALTUNG = 5;
	public static $POLICIES = array(
		4 => "Niemand",
		0 => "Nur Admins",
		1 => "Nur Parteimitglieder (Wurzelwerk)",
		2 => "Nur eingeloggte Benutzer",
		5 => "Alle (mit Freischaltung)",
		3 => "Alle",
	);

	public static $TYP_PARTEITAG = 0;
	public static $TYP_PROGRAMM = 1;
	public static $TYPEN = array(
		0 => "Parteitag",
		1 => "(Wahl-)Programm",
	);


	/** @var null|VeranstaltungsEinstellungen */
	private $einstellungen_object = null;

	/**
	 * @return VeranstaltungsEinstellungen
	 */
	public function getEinstellungen()
	{
		if (!is_object($this->einstellungen_object)) $this->einstellungen_object = new VeranstaltungsEinstellungen($this->einstellungen);
		return $this->einstellungen_object;
	}

	/**
	 * @param VeranstaltungsEinstellungen $einstellungen
	 */
	public function setEinstellungen($einstellungen)
	{
		$this->einstellungen_object = $einstellungen;
		$this->einstellungen        = $einstellungen->toJSON();
	}

	/** @return IPolicyAntraege */
	public function getPolicyAntraege()
	{
		if (is_null($this->policy_antraege_obj)) $this->policy_antraege_obj = IPolicyAntraege::getInstanceByID($this->policy_antraege, $this);
		return $this->policy_antraege_obj;
	}

	/** @return IPolicyAntraege */
	public function getPolicyAenderungsantraege()
	{
		if (is_null($this->policy_aenderungsantraege_obj)) $this->policy_aenderungsantraege_obj = IPolicyAntraege::getInstanceByID($this->policy_aenderungsantraege, $this);
		return $this->policy_aenderungsantraege_obj;
	}

	/** @return IPolicyUnterstuetzen */
	public function getPolicyUnterstuetzen()
	{
		if (is_null($this->policy_unterstuetzen_obj)) $this->policy_unterstuetzen_obj = IPolicyUnterstuetzen::getInstanceByID($this->policy_unterstuetzen, $this);
		return $this->policy_unterstuetzen_obj;
	}

	/**
	 * @param int $policy
	 * @return bool
	 */
	private function darfEroeffnen_intern($policy)
	{
		switch ($policy) {
			case Veranstaltung::$POLICY_ALLE:
				return true;
			case Veranstaltung::$POLICY_ALLE_FREISCHALTUNG:
				return true;
			case Veranstaltung::$POLICY_REGISTRIERTE:
				return !Yii::app()->user->isGuest;
			case Veranstaltung::$POLICY_NUR_ADMINS:
				if (Yii::app()->user->isGuest) return false;
				return (Yii::app()->user->getState("role") == "admin");
			case Veranstaltung::$POLICY_PARTEIMITGLIEDER:
				if (Yii::app()->user->isGuest) return false;
				return (preg_match("/^openid:https:\/\/[a-z0-9_-]+\.netzbegruener\.in\/$/i", Yii::app()->user->id));
			case Veranstaltung::$POLICY_NIEMAND:
				return false;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function darfEroeffnenKommentar()
	{
		return $this->darfEroeffnen_intern($this->policy_kommentare);
	}


	/**
	 * @return Sprache
	 */
	public function getSprache()
	{
		switch ($this->typ) {
			case Veranstaltung::$TYP_PROGRAMM:
				return new SpracheProgramm();
			default:
				return new SpracheAntraege();
		}
	}


	/**
	 * @return array|string[]
	 */
	public static function getStandardtextIDs()
	{
		return array("startseite", "impressum", "hilfe", "antrag_confirm");
	}

	/**
	 * @return array|string[]
	 */
	public static function getHTMLStandardtextIDs()
	{
		return array("startseite", "impressum", "hilfe", "antrag_eingereicht", "antrag_confirm", "ae_eingereicht", "ae_confirm", "wartungsmodus");
	}

	/**
	 * @param string $id
	 * @return Standardtext
	 */
	public function getStandardtext($id)
	{
		$vtext = Texte::model()->findByAttributes(array("text_id" => $id, "veranstaltung_id" => $this->id));
		/** @var Texte|null $vtext */

		if (is_null($vtext)) {
			$edit_link   = array("admin/texte/create", array("key" => $id, "veranstaltung_id" => $this->url_verzeichnis));
			$vtext       = Texte::model()->findByAttributes(array("text_id" => $id, "veranstaltung_id" => null));
			$is_fallback = true;
		} else {
			$edit_link   = array("admin/texte/update", array("id" => $vtext->id, "veranstaltung_id" => $this->url_verzeichnis));
			$is_fallback = false;
		}

		$text = (is_null($vtext) ? "" : $vtext->text);

		if (!$this->isAdminCurUser()) $edit_link = null;

		$html = in_array($id, Veranstaltung::getHTMLStandardtextIDs());

		return new Standardtext($id, $text, $html, $edit_link, $is_fallback);
	}


	/**
	 * @param int $antrag_typ
	 * @return string
	 */
	public function naechsteAntragRevNr($antrag_typ)
	{
		$max_rev     = 0;
		$andereantrs = $this->antraege;
		foreach ($andereantrs as $antr) if ($antr->typ == $antrag_typ) {
			$revs  = substr($antr->revision_name, strlen(Antrag::$TYP_PREFIX[$antr->typ]));
			$revnr = IntVal($revs);
			if ($revnr > $max_rev) $max_rev = $revnr;
		}
		return Antrag::$TYP_PREFIX[$antrag_typ] . ($max_rev + 1);
	}

	/**
	 * @return array|array[]
	 */
	public function antraegeSortiert()
	{
		$antraege        = $this->antraege;
		$antraege_sorted = array();
		// $warnung         = false;
		foreach ($antraege as $ant) if (!in_array($ant->status, IAntrag::$STATI_UNSICHTBAR)) {
			if (!isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]])) $antraege_sorted[Antrag::$TYPEN[$ant->typ]] = array();
			$key = $ant->revision_name;
			/*
			if (isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key]) && !$warnung) {
				$warnung = true;
				Yii::app()->user->setFlash("error", "Es können nicht alle Anträge angezeigt werden, da mindestens ein Kürzel ($key) mehrfach vergeben ist.");
			}
			*/
			$antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key] = $ant;
		}
		if (!in_array($this->url_verzeichnis, array("ltwby13-programm", "btw13-programm"))) foreach ($antraege_sorted as $key => $val) {
			ksort($antraege_sorted[$key]);
		}
		return $antraege_sorted;
	}

	/**
	 * @param Person $person
	 * @return bool
	 */
	public function isAdmin($person)
	{
		foreach ($this->admins as $e) if ($e->id == $person->id) return true;
		return $this->veranstaltungsreihe->isAdmin($person);
	}

	/**
	 * @return bool
	 */
	public function isAdminCurUser()
	{
		$user = Yii::app()->user;
		if ($user->isGuest) return false;
		if ($user->getState("role") === "admin") return true;
		$ich = Person::model()->findByAttributes(array("auth" => $user->id));
		/** @var Person $ich */
		if ($ich == null) return false;
		return $this->isAdmin($ich);
	}

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
			array('name, freischaltung_antraege, url_verzeichnis, freischaltung_aenderungsantraege, revision_name_verstecken, kommentare_unterstuetzbar, ansicht_minimalistisch, freischaltung_kommentare, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, ae_nummerierung_global, zeilen_nummerierung_global, bestaetigungs_emails, einstellungen', 'required'),
			array('name, logo_url, fb_logo_url', 'length', 'max' => 200),
			array('name_kurz, url_verzeichnis', 'length', 'max' => 45),
			array('antragsschluss, antrag_einleitung, admin_email', 'safe'),
			array('antragsschluss', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, url_verzeichnis, logo_url, fb_logo_url, freischaltung_antraege, name_kurz, revision_name_verstecken, kommentare_unterstuetzbar, antrag_einleitung, datum_von, datum_bis, antragsschluss, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, ae_nummerierung_global, zeilen_nummerierung_global, bestaetigungs_emails, einstellungen', 'safe', 'on' => 'search'),
		);
	}

	public function relations()
	{
		return array(
			'antraege' => array(self::HAS_MANY, 'Antrag', 'veranstaltung_id'),
			'admins'   => array(self::MANY_MANY, 'Person', 'veranstaltungs_admins(veranstaltung_id, person_id)'),
			'texte'    => array(self::HAS_MANY, 'Texte', 'veranstaltung_id'),
			'veranstaltungsreihe' => array(self::BELONGS_TO, 'Veranstaltungsreihe', 'veranstaltungsreihe_id'),
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
			'url_verzeichnis'                  => Yii::t('app', 'Unterverzeichnis'),
			'logo_url'                         => Yii::t('app', 'Logo-URL'),
			'fb_logo_url'                      => Yii::t('app', 'Facebook-Bild URL'),
			'antraege'                         => null,
			'admins'                           => null,
			'texte'                            => null,
			'veranstaltungsreihe'              => Yii::t('app', 'Veranstaltungsreihe'),
			'ae_nummerierung_global'           => Yii::t('app', 'ÄA-Nummerierung für die ganze Veranstaltung'),
			'zeilen_nummerierung_global'       => Yii::t('app', 'Zeilennummerierung durchgehend für die ganze Veranstaltung'),
			'bestaetigungs_emails'             => Yii::t('app', 'Bestätigungsmails an AntragsStellerInnen'),
			'revision_name_verstecken'         => Yii::t('app', 'Revisionsname verstecken'),
			'kommentare_unterstuetzbar'        => Yii::t('app', 'Kommentare unterstützbar'),
			'ansicht_minimalistisch'           => Yii::t('app', 'Minimalistische Ansicht'),
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


	public function save($runValidation = true, $attributes = null)
	{
		Yii::app()->cache->delete("pdf_" . $this->id);
		return parent::save($runValidation, $attributes);
	}
}