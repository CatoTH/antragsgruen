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

	public function actionAePDFList($veranstaltungsreihe_id = "", $veranstaltung_id = "") {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		$criteria = new CDbCriteria();
		$criteria->alias = "aenderungsantrag";
		$criteria->order = "LPAD(REPLACE(aenderungsantrag.revision_name, 'Ä', ''), 3, '0')";
		$criteria->addNotInCondition("aenderungsantrag.status", IAntrag::$STATI_UNSICHTBAR);
		$aenderungsantraege = Aenderungsantrag::model()->with(array(
			"antrag" => array('condition' => 'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id))
		))->findAll($criteria);
		$this->render("ae_pdf_list", array("aes" => $aenderungsantraege));
	}

	public function actionAeExcelList($veranstaltungsreihe_id = "", $veranstaltung_id = "") {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		ini_set('memory_limit', '256M');

		$antraege_sorted = $this->veranstaltung->antraegeSortiert();
		$antrs = array();
		foreach ($antraege_sorted as $gruppe) foreach ($gruppe as $antr) {
			/** @var Antrag $antr */
			/** @var Aenderungsantrag[] $aes */

			//if (!in_array($antr->id, array(258, 86))) continue; // @TODO
			$aes = array();
			foreach ($antr->aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) $aes[] = $ae;

			usort($aes, function($ae1, $ae2) {
				/** @var Aenderungsantrag $ae1 */
				/** @var Aenderungsantrag $ae2 */
				$first1 = $ae1->getFirstDiffLine();
				$first2 = $ae2->getFirstDiffLine();

				if ($first1 < $first2) return -1;
				if ($first1 > $first2) return 1;

				$x1 = explode("-", $ae1->revision_name);
				$x2 = explode("-", $ae2->revision_name);
				if (count($x1) == 3 && count($x2) == 3) {
					if ($x1[2] < $x2[2]) return -1;
					if ($x1[2] > $x2[2]) return 1;
					return 0;
				} else {
					return strcasecmp($ae1->revision_name, $ae2->revision_name);
				}
			});
			$antrs[] = array(
				"antrag" => $antr,
				"aes" => $aes
			);
		}

		$this->renderPartial("ae_excel_list", array("antraege" => $antrs));
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

			if ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare) {
				/** @var array|AntragKommentar[] $kommentare  */
				$kommentare = AntragKommentar::model()->with(array(
					"antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
				))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
				foreach ($kommentare as $komm) {
					$todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->antrag->revision_name, array("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
				}
			}


		}

		$this->render('index', array(
			"todo" => $todo
		));
	}

}