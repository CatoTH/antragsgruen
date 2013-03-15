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
	public static function holeNeueste($veranstaltung_id = 0, $limit = 0) {
		$condition = ($limit > 0 ? array("limit" => $limit) : "");
		$arr = AntragKommentar::model()->with(array(
			"antrag" => array(
				"condition" => "antrag.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND antrag.veranstaltung = " . IntVal($veranstaltung_id)
			),
		))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->antrag->veranstaltung0;
	}
}