<?php

Yii::import('application.models._base.BaseAntragKommentar');

class AntragKommentar extends BaseAntragKommentar
{
    /**
     * @var $clasName string
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|AntragKommentar[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 3) {
		$oCriteria        = new CDbCriteria();
		$oCriteria->alias = "antrag_kommentar";
		$oCriteria->addNotInCondition("antrag_kommentar.status", array(IKommentar::$STATUS_GELOESCHT));
		$oCriteria->with = "antrag";
		if ($veranstaltung_id > 0) $oCriteria->addCondition("antrag.veranstaltung = " . IntVal($veranstaltung_id));
		$oCriteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->order   = 'datum DESC';
		$dataProvider       = new CActiveDataProvider('AntragKommentar', array(
			'criteria'      => $oCriteria,
			'pagination'    => array(
				'pageSize'      => IntVal($limit),
			),
		));
		return $dataProvider->data;
	}
}