<?php

Yii::import('application.models._base.BaseAntragUnterstuetzer');

class AntragUnterstuetzer extends BaseAntragUnterstuetzer
{

     /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

    public function relations() {
        $ret = parent::relations();
        $ret["unterstuetzer"]["order"] = "`unterstuetzer`.`name` ASC";
        return $ret;
    }
}