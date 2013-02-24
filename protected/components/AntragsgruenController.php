<?php
/**
 * AntragsgruenController is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class AntragsgruenController extends CController
{
	public $layout='//layouts/column1';
	public $menu=array();
	public $breadcrumbs=array();
	public $multimenu = null;
	public $menus_html = null;
	public $breadcrumbs_topname = null;
	public $text_comments = true;
	public $shrink_cols = false;

	/** @var null|Veranstaltung */
	public $veranstaltung = null;

	/**
	 *
	 */
	protected function setStdVeranstaltung() {
		$veranstaltung_id = (isset($_REQUEST["id"]) ? IntVal($_REQUEST["id"]) : Yii::app()->params['standardVeranstaltung']);
		$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return string
	 */
	public function createUrl($path, $params = array()) {
		if (!isset($params["veranstaltung_id"]) && $this->veranstaltung !== null) $params["veranstaltung_id"] = $this->veranstaltung->yii_url;
		return parent::createUrl($path, $params);
	}

	/**
	 * @param int|string $veranstaltung_id
	 * @param null|Antrag $check_antrag
	 * @param null|Aenderungsantrag $check_aenderungsantrag
	 * @return null|Veranstaltung
	 */
	public function loadVeranstaltung($veranstaltung_id, $check_antrag = null, $check_aenderungsantrag = null) {
		if (is_null($this->veranstaltung)) {
			if (is_numeric($veranstaltung_id)) {
				$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
			} else {
				$this->veranstaltung = Veranstaltung::model()->findByAttributes(array("yii_url" => $veranstaltung_id));
			}
		}

		if (is_object($check_antrag) && $check_antrag->veranstaltung0->yii_url != $veranstaltung_id) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Antrag gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("site/veranstaltung", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if ($check_aenderungsantrag != null && ($check_antrag == null || $check_aenderungsantrag->antrag_id != $check_antrag->id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Änderungsantrag gehört nicht zum Antrag.");
			$this->redirect($this->createUrl("site/veranstaltung", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		return $this->veranstaltung;
	}
}