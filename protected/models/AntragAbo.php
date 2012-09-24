<?php

Yii::import('application.models._base.BaseAntragAbo');

class AntragAbo extends BaseAntragAbo
{
    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
}