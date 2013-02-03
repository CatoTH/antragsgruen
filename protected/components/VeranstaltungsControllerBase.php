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

	protected function setStdVeranstaltung() {
		$veranstaltung_id = (isset($_REQUEST["id"]) ? IntVal($_REQUEST["id"]) : Yii::app()->params['standardVeranstaltung']);
		$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
	}
}