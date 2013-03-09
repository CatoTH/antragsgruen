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
		$veranstaltungs_where = ($veranstaltung_id > 0 ? "AND c.veranstaltung = " . IntVal($veranstaltung_id) : "");
		$limit = ($limit > 0 ? "LIMIT 0, " . IntVal($limit) : "");
		$SQL = "SELECT a.* FROM aenderungsantrag_kommentar a JOIN aenderungsantrag b ON a.aenderungsantrag_id = b.id JOIN antrag c ON c.id = b.antrag_id
			WHERE a.status != " . IKommentar::$STATUS_GELOESCHT . " AND b.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND c.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")
			$veranstaltungs_where ORDER BY a.datum DESC $limit";

		$list= Yii::app()->db->createCommand($SQL)->queryAll();
		$arr = array();
		foreach ($list as $l) {
			$x = new AenderungsantragKommentar();
			$x->attributes = $l;
			$arr[] = $x;
		}
		return $arr;
	}
}