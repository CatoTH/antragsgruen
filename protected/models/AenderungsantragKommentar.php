<?php

Yii::import('application.models._base.BaseAenderungsantragKommentar');

class AenderungsantragKommentar extends BaseAenderungsantragKommentar
{
    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->aenderungsantrag->antrag->veranstaltung0;
	}
}