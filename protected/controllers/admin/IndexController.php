<?php

class IndexController extends AntragsgruenController
{
	public $layout = '//layouts/column1';

	public function actionKommentareexcel($veranstaltungsreihe_id = "", $veranstaltung_id = "") {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$kommentare = array();

		$aenderung = AenderungsantragKommentar::holeNeueste($this->veranstaltung->id);
		foreach ($aenderung as $ant) $kommentare[] = $ant;

		$antraege = AntragKommentar::holeNeueste($this->veranstaltung->id);
		foreach ($antraege as $ant) $kommentare[] = $ant;

		$kommentare = array_reverse($kommentare);

		$this->renderPartial('kommentare_excel', array(
			"kommentare" => $kommentare
		));
	}

	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$todo = array(
//			array("Text anlegen", array("admin/texte/update", array())),
		);

		if (!is_null($this->veranstaltung)) {
			$standardtexte = $this->veranstaltung->getHTMLStandardtextIDs();
			foreach ($standardtexte as $text) {
				$st = Texte::model()->findByAttributes(array("veranstaltung_id" => $this->veranstaltung->id, "text_id" => $text));
				if ($st == null) $todo[] = array("Text anlegen: " . $text, array("admin/texte/create", array("key" => $text)));
			}

			/** @var array|Antrag[] $antraege */
			$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $this->veranstaltung->id, "status" => Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
			foreach ($antraege as $antrag) {
				$todo[] = array("Antrag prüfen: " . $antrag->revision_name . " " . $antrag->name, array("admin/antraege/update", array("id" => $antrag->id)));
			}

			/** @var array|Aenderungsantrag[] $aenderungs */
			$aenderungs = Aenderungsantrag::model()->with(array(
				"antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
			))->findAllByAttributes(array("status" => Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
			foreach ($aenderungs as $ae) {
				$todo[] = array("Änderungsanträge prüfen: " . $ae->revision_name . " zu " . $ae->antrag->revision_name . " " . $ae->antrag->name, array("admin/aenderungsantraege/update", array("id" => $ae->id)));
			}

			if ($this->veranstaltung->freischaltung_kommentare) {
				/** @var array|AntragKommentar[] $kommentare  */
				$kommentare = AntragKommentar::model()->with(array(
					"antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
				))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
				foreach ($kommentare as $komm) {
					$todo[] = array("Kommentar prüfen: " . $komm->verfasser->name . " zu " . $komm->antrag->revision_name, array("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
				}
			}


		}

		$this->render('index', array(
			"todo" => $todo
		));
	}

}