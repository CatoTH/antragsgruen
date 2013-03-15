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


	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|AenderungsantragKommentar[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 0) {
		$antrag_ids = array();
		/** @var array|Antrag[] $antraege */
		$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung" => $veranstaltung_id));
		foreach ($antraege as $a) $antrag_ids[] = $a->id;

		if (count($antrag_ids) == 0) return array();

		$condition = ($limit > 0 ? array("limit" => $limit) : "");
		$arr = AenderungsantragKommentar::model()->with(array(
			"aenderungsantrag" => array(
				"condition" => "aenderungsantrag.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND aenderungsantrag.antrag_id IN (" . implode(", ", $antrag_ids) . ")"
			),
		))->findAllByAttributes(array("status" => AenderungsantragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}
}