<?php

/**
 * @property integer $verfasser_id
 * @property Person $verfasser
 * @property integer $status
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
	 * @param CWebUser $c
	 * @return bool
	 */
	public function kannLoeschen($c) {
		if ($this->getVeranstaltung()->isAdminCurUser()) return true;
		if (!is_null($this->verfasser->auth) && $c->getId() == $this->verfasser->auth) return true;
		return false;
	}

	/**
	 * @return bool
	 */
	public function istSichtbarCurrUser() {
		if ($this->status == static::$STATUS_GELOESCHT) return false;
		if ($this->status == static::$STATUS_FREI) return true;
		if (Yii::app()->user->getState("role") == "admin") return true;
		if ($this->getVeranstaltung()->isAdminCurUser()) return true;

		$user = Yii::app()->user;
		if ($user->isGuest) return false;
		if ($user->getState("role") === "admin") return true;
		/** @var Person $ich  */
		$ich = Person::model()->findByAttributes(array("auth" => $user->id));
		return ($ich->id == $this->verfasser_id);
	}

}
