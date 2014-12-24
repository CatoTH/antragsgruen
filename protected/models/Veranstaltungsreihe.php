<?php

/**
 * @property integer $id
 * @property string $subdomain
 * @property string $name
 * @property string $name_kurz
 * @property bool $offiziell
 * @property bool $oeffentlich
 * @property string $einstellungen
 * @property int $aktuelle_veranstaltung_id
 * @property int $zugang
 * @property string $kontakt_intern
 *
 * @property Veranstaltung[] $veranstaltungen
 * @property Veranstaltung $aktuelle_veranstaltung
 * @property Person[] $admins
 * @property VeranstaltungsreihenAbo[] $veranstaltungsreihenAbos
 */
class Veranstaltungsreihe extends CActiveRecord
{

	public static $ZUGANG_ALLE = 0;
	public static $ZUGANG_NAMESPACE_LOGIN = 1;
	public static $ZUGANG_TYPEN = array(
		0 => "Alle / Öffentlich",
		1 => "Nur vom Admin eingetragene NutzerInnen",
	);

	/** @var null|VeranstaltungsreihenEinstellungen */
	private $einstellungen_object = null;

	/**
	 * @return VeranstaltungsreihenEinstellungen
	 */
	public function getEinstellungen()
	{
		if (!is_object($this->einstellungen_object)) $this->einstellungen_object = new VeranstaltungsreihenEinstellungen($this->einstellungen);
		return $this->einstellungen_object;
	}

	/**
	 * @param VeranstaltungsreihenEinstellungen $einstellungen
	 */
	public function setEinstellungen($einstellungen)
	{
		$this->einstellungen_object = $einstellungen;
		$this->einstellungen        = $einstellungen->toJSON();
	}


	/**
	 * @param Person $person
	 * @return bool
	 */
	public function isAdmin($person)
	{
		foreach ($this->admins as $e) if ($e->id == $person->id) return true;
		if (Yii::app()->params['admin_user_id'] !== null && $person->id == Yii::app()->params['admin_user_id']) return true;
		return false;
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
	 * @return Veranstaltungsreihe[]
	 */
	public static function getSidebarReihen()
	{
		/** @var Veranstaltungsreihe[] $reihen */
		$reihen  = Veranstaltungsreihe::model()->findAllByAttributes(array("oeffentlich" => 1), array("order" => "id DESC"));
		$reihen2 = array();
		foreach ($reihen as $reihe) {
			if ($reihe->aktuelle_veranstaltung && !$reihe->aktuelle_veranstaltung->getEinstellungen()->wartungs_modus_aktiv) $reihen2[] = $reihe;
		}
		return $reihen2;
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
		return 'veranstaltungsreihe';
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'Veranstaltungsreihe|Veranstaltungsreihen', $n);
	}

	public static function representingColumn()
	{
		return 'name';
	}

	public function rules()
	{
		return array(
			array('subdomain, name, name_kurz, kontakt_intern', 'required'),
			array('name', 'length', 'max' => 200),
			array('offiziell, oeffentlich', 'boolean'),
			array('zugang', 'numerical', 'integerOnly' => true),
			array('subdomain', 'length', 'max' => 45),
			array('name', 'length', 'max' => 100),
			array('name, name_kurz', 'safe'),
		);
	}

	public function relations()
	{
		return array(
			'veranstaltungen'          => array(self::HAS_MANY, 'Veranstaltung', 'veranstaltungsreihe_id'),
			'admins'                   => array(self::MANY_MANY, 'Person', 'veranstaltungsreihen_admins(veranstaltungsreihe_id, person_id)'),
			'aktuelle_veranstaltung'   => array(self::BELONGS_TO, 'Veranstaltung', 'aktuelle_veranstaltung_id'),
			'veranstaltungsreihenAbos' => array(self::HAS_MANY, 'VeranstaltungsreihenAbo', 'veranstaltungsreihe_id'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id'                       => Yii::t('app', 'ID'),
			'name'                     => Yii::t('app', 'Name'),
			'name_kurz'                => Yii::t('app', 'Name Kurz'),
			'subdomain'                => Yii::t('app', 'Subdomain'),
			'einstellungen'            => "Einstellungen",
			'offiziell'                => 'Offizielle Veranstaltungsreihe',
			'oeffentlich'              => 'Öffentlich',
			'veranstaltungen'          => 'Veranstaltungen',
			'aktuelle_veranstaltung'   => 'Aktuelle Veranstaltung',
			'zugang'                   => 'Zugangsbeschränkung',
			'admins'                   => null,
			'veranstaltungsreihenAbos' => null,
		);
	}

	public function save($runValidation = true, $attributes = null)
	{
		Yii::app()->cache->delete("pdf_" . $this->id);
		return parent::save($runValidation, $attributes);
	}
}