<?php

Yii::import('application.models._base.BaseAenderungsantragUnterstuetzer');

class AenderungsantragUnterstuetzer extends BaseAenderungsantragUnterstuetzer
{

    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}