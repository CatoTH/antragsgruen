<?php

class IndexController extends AntragsgruenController
{
	public $layout = '//layouts/column1';

	public function actionKommentareexcel($veranstaltung_id = "") {
		if ($veranstaltung_id == "") $veranstaltung_id = Yii::app()->params['standardVeranstaltung'];
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

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

	public function actionIndex($veranstaltung_id = "")
	{
		if ($veranstaltung_id == "") $veranstaltung_id = Yii::app()->params['standardVeranstaltung'];
		$this->loadVeranstaltung($veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

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
			$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung" => $this->veranstaltung->id, "status" => Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
			foreach ($antraege as $antrag) {
				$todo[] = array("Antrag prüfen: " . $antrag->revision_name . " " . $antrag->name, array("admin/antraege/update", array("id" => $antrag->id)));
			}

			/** @var array|Aenderungsantrag[] $aenderungs */
			$aenderungs = Aenderungsantrag::model()->with(array(
				"antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung = " . IntVal($this->veranstaltung->id))
			))->findAllByAttributes(array("status" => Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT));
			foreach ($aenderungs as $ae) {
				$todo[] = array("Änderungsanträge prüfen: " . $ae->revision_name . " zu " . $ae->antrag->revision_name . " " . $ae->antrag->name, array("admin/aenderungsantraege/update", array("id" => $ae->id)));
			}


		}

		$this->render('index', array(
			"todo" => $todo
		));
	}

}