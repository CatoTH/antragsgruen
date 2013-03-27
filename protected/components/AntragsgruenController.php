<?php
/**
 * AntragsgruenController is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class AntragsgruenController extends CController
{
	public $layout = '//layouts/column1';
	public $menu = array();
	public $breadcrumbs = array();
	public $multimenu = null;
	public $menus_html = null;
	public $breadcrumbs_topname = null;
	public $text_comments = true;
	public $shrink_cols = false;

	/** @var null|Veranstaltung */
	public $veranstaltung = null;

	/** @var null|Veranstaltungsreihe */
	public $veranstaltungsreihe = null;


	private $_assetsBase;

	/**
	 *
	 */
	public function testeWartungsmodus()
	{
		if ($this->veranstaltung == null) return;
		/** @var VeranstaltungsEinstellungen $einstellungen */
		$einstellungen = $this->veranstaltung->getEinstellungen();
		if ($einstellungen->wartungs_modus_aktiv && !$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("veranstaltung/wartungsmodus"));
	}

	/**
	 *
	 */
	protected function setStdVeranstaltung()
	{
		$veranstaltung_id    = (isset($_REQUEST["id"]) ? IntVal($_REQUEST["id"]) : Yii::app()->params['standardVeranstaltung']);
		$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return string
	 */
	public function createUrl($path, $params = array())
	{
		if (!isset($params["veranstaltung_id"]) && $this->veranstaltung !== null) $params["veranstaltung_id"] = $this->veranstaltung->url_verzeichnis;
		if (!isset($params["veranstaltungsreihe_id"]) && $this->veranstaltungsreihe != null) $params["veranstaltungsreihe_id"] = $this->veranstaltungsreihe->subdomain;
		if ($path == "veranstaltung/index" && !is_null($this->veranstaltungsreihe) && $params["veranstaltung_id"] == $this->veranstaltungsreihe->aktuelle_veranstaltung->url_verzeichnis) unset($params["veranstaltung_id"]);
		return parent::createUrl($path, $params);
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param int|string $veranstaltung_id
	 * @param null|Antrag $check_antrag
	 * @param null|Aenderungsantrag $check_aenderungsantrag
	 * @return null|Veranstaltung
	 */
	public function loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $check_antrag = null, $check_aenderungsantrag = null)
	{
		if ($veranstaltungsreihe_id == "") $veranstaltungsreihe_id = Yii::app()->params['standardVeranstaltungsreihe'];

		if (is_null($this->veranstaltungsreihe)) {
			if (is_numeric($veranstaltungsreihe_id)) {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByPk($veranstaltungsreihe_id);
			} else {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $veranstaltungsreihe_id));
			}
		}

		if (is_null($this->veranstaltung)) {
			if (is_numeric($veranstaltung_id)) {
				$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
			} else {
				$this->veranstaltung = Veranstaltung::model()->findByAttributes(array("url_verzeichnis" => $veranstaltung_id));
			}
		}

		if ($this->veranstaltung->veranstaltungsreihe->subdomain != $veranstaltungsreihe_id) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - die Veranstaltung gehört nicht zur Veranstaltungsreihe.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (is_object($check_antrag) && $check_antrag->veranstaltung->url_verzeichnis != $veranstaltung_id) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Antrag gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if ($check_aenderungsantrag != null && ($check_antrag == null || $check_aenderungsantrag->antrag_id != $check_antrag->id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Änderungsantrag gehört nicht zum Antrag.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (!is_a($this->veranstaltung, "Veranstaltung") || $this->veranstaltung->policy_kommentare == Veranstaltung::$POLICY_NIEMAND) $this->text_comments = false;

		return $this->veranstaltung;
	}


	public function getAssetsBase()
	{
		if ($this->_assetsBase === null) {
			$this->_assetsBase = Yii::app()->assetManager->publish(
				Yii::getPathOfAlias('application.assets'),
				false,
				-1,
				defined('YII_DEBUG') && YII_DEBUG
			);
		}
		return $this->_assetsBase;
	}

}