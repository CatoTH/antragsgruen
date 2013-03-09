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
		$veranstaltungs_where = ($veranstaltung_id > 0 ? "AND c.veranstaltung = " . IntVal($veranstaltung_id) : "");
		$limit = ($limit > 0 ? "LIMIT 0, " . IntVal($limit) : "");
		$SQL = "SELECT a.* FROM antrag_kommentar a JOIN antrag c ON c.id = a.antrag_id
			WHERE a.status != " . IKommentar::$STATUS_GELOESCHT . " AND c.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")
			$veranstaltungs_where ORDER BY a.datum DESC $limit";

		$list= Yii::app()->db->createCommand($SQL)->queryAll();
		$arr = array();
		foreach ($list as $l) {
			$x = new AntragKommentar();
			$x->attributes = $l;
			$arr[] = $x;
		}
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