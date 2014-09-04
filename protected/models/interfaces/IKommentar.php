<?php

/**
 * @property integer $id
 * @property integer $verfasserIn_id
 * @property integer $absatz
 * @property string $text
 * @property string $datum
 * @property integer $status
 * @property Person $verfasserIn
 */
abstract class IKommentar extends GxActiveRecord {

	public static $STATUS_NICHT_FREI = 1;
	public static $STATUS_FREI = 0;
	public static $STATUS_GELOESCHT = -1;
	public static $STATI = array(
		1 => "Nicht freigeschaltet",
		-1 => "Gelöscht",
		0 => "Sichtbar",
	);


	/**
	 * @return Veranstaltung
	 */
	abstract public function getVeranstaltung();

	/**
	 * @return string
	 */
	abstract public function getAntragName();

	/**
	 * @param bool $absolute
	 * @return string
	 */
	abstract public function getLink($absolute = false);

	/**
	 * @param CWebUser $c
	 * @return bool
	 */
	public function kannLoeschen($c) {
		if ($this->getVeranstaltung()->isAdminCurUser()) return true;
		if (!is_null($this->verfasserIn->auth) && $c->getId() == $this->verfasserIn->auth) return true;
		return false;
	}

	/**
	 * @return bool
	 */
	public function istSichtbarCurrUser() {
		if ($this->status == static::$STATUS_GELOESCHT) return false;
		if ($this->status == static::$STATUS_FREI) return true;
		if ($this->getVeranstaltung()->isAdminCurUser()) return true;

		$user = Yii::app()->user;
		if ($user->isGuest) return false;
		/** @var Person $ich  */
		$ich = Person::model()->findByAttributes(array("auth" => $user->id));
		return ($ich->id == $this->verfasserIn_id);
	}

}
