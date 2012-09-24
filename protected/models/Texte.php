<?php

Yii::import('application.models._base.BaseTexte');

class Texte extends BaseTexte
{
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Text|Texte', $n);
	}
}