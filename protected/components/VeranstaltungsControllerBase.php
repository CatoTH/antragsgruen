<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tobias
 * Date: 03.02.13
 * Time: 19:09
 * To change this template use File | Settings | File Templates.
 */

class VeranstaltungsControllerBase extends Controller {
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
		if (!isset($params["veranstaltung_id"])) $params["veranstaltung_id"] = $this->veranstaltung->yii_url;
		return parent::createUrl($path, $params);
	}

	/**
	 * @param int|string $veranstaltung_id
	 * @return null|Veranstaltung
	 */
	public function loadVeranstaltung($veranstaltung_id) {
		if (is_null($this->veranstaltung)) {
			if (is_numeric($veranstaltung_id)) {
				$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
			} else {
				$this->veranstaltung = Veranstaltung::model()->findByAttributes(array("yii_url" => $veranstaltung_id));
			}
		}
		return $this->veranstaltung;
	}
}