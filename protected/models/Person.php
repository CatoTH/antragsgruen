<?php

Yii::import('application.models._base.BasePerson');

class Person extends BasePerson
{
        public static $TYP_ORGANISATION = 'organisation';
        public static $TYP_PERSON = 'person';
        public static $TYPEN = array(
            'organisation' => "Organisation",
            'person' => "Natürliche Person",
        );
        
        public static $STATUS_UNCONFIRMED = 1;
        public static $STATUS_CONFIRMED = 0;
        public static $STATUS_DELETED = -1;
        public static $STATUS = array(
            1 => "Nicht bestätigt",
            0 => "Bestätigt",
            -1 => "Gelöscht",
        );

    /**
     * @var $className string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

    public static function label($n = 1) {
        return Yii::t('app', 'Person|Personen', $n);
    }

	/*
    public function attributeLabels() {
        $ret = parent::attributeLabels();
        $ret["abonnenten"] = "Hat abonniert";
        return $ret;
    }
	*/

}