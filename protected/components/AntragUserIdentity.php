<?php

class AntragUserIdentity extends CBaseUserIdentity
{
	/**
	 * @var LightOpenID
	 */
	private $_loid;

	/**
	 * @param LightOpenID $loid
	 */
	public function __construct($loid)
	{
		$this->_loid = $loid;
	}


	/**
	 * @return Bool
	 */
	public function authenticate()
	{
		return $this->_loid->validate();
	}


	/**
	 * @return string
	 */
	public function getId()
	{
		return "openid:" . $this->_loid->identity;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		$atts = $this->_loid->getAttributes();
		if (isset($atts["namePerson/friendly"])) return $atts["namePerson/friendly"];
		if (isset($atts["contact/email"])) return $atts["contact/email"];
		return $this->_loid->identity;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		$atts = $this->_loid->getAttributes();
		if (isset($atts["contact/email"])) return $atts["contact/email"];
		return "";
	}


	/**
	 * @static
	 * @param array $submit_data
	 * @param int $submit_status
	 * @return Person
	 */
	public static function getCurrenPersonOrCreateBySubmitData($submit_data, $submit_status)
	{
		if (Yii::app()->user->isGuest) {
			$model_person = Person::model()->findByAttributes(array("typ" => $submit_data["typ"], "name" => trim($submit_data["name"]), "status" => $submit_status));
			if (!$model_person) {
				$model_person                 = new Person();
				$model_person->attributes     = $submit_data;
				$model_person->admin          = 0;
				$model_person->angelegt_datum = new CDbExpression('NOW()');
				$model_person->status         = $submit_status;

				if (!$model_person->save()) {
					foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Person konnte nicht angelegt werden: $key: $val2");
					$model_person = null;
				} else {
					Yii::app()->user->setState("person_id", $model_person->id);
				}
			}
		} else {
			$model_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}
		return $model_person;
	}
}