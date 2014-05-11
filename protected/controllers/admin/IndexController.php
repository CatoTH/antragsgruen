<?php

class IndexController extends AntragsgruenController
{
	public $layout = '//layouts/column1';

	public function actionKommentareexcel($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
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

	public function actionAePDFList($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		$criteria        = new CDbCriteria();
		$criteria->alias = "aenderungsantrag";
		$criteria->order = "LPAD(REPLACE(aenderungsantrag.revision_name, 'Ä', ''), 3, '0')";
		$criteria->addNotInCondition("aenderungsantrag.status", IAntrag::$STATI_UNSICHTBAR);
		$criteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$aenderungsantraege = Aenderungsantrag::model()->with(array(
			"antrag" => array('condition' => 'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id))
		))->findAll($criteria);
		$this->render("ae_pdf_list", array("aes" => $aenderungsantraege));
	}

	public function actionAeExcelList($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		ini_set('memory_limit', '256M');

		$antraege_sorted = $this->veranstaltung->antraegeSortiert();
		$antrs           = array();
		foreach ($antraege_sorted as $gruppe) foreach ($gruppe as $antr) {
			/** @var Antrag $antr */
			/** @var Aenderungsantrag[] $aes */

			//if (!in_array($antr->id, array(258, 86))) continue; // @TODO
			$aes = array();
			foreach ($antr->aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) $aes[] = $ae;

			usort($aes, function ($ae1, $ae2) {
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
				"aes"    => $aes
			);
		}

		$this->renderPartial("ae_excel_list", array("antraege" => $antrs));
	}

	public function actionReiheAdmins($veranstaltungsreihe_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$user = Yii::app()->user;
		$ich  = Person::model()->findByAttributes(array("auth" => $user->id));

		if (AntiXSS::isTokenSet("adduser")) {
			$user = Person::getWurzelwerkler($_REQUEST["username"]);
			if ($user) {
				try {
					Yii::app()->db->createCommand()->insert("veranstaltungsreihen_admins", array("veranstaltungsreihe_id" => $this->veranstaltungsreihe->id, "person_id" => $user->id));
					Yii::app()->user->setFlash("success", $_REQUEST["username"] . " hat nun auch Admin-Rechte");
					$this->veranstaltungsreihe->refresh();
				} catch (Exception $e) {
					Yii::app()->user->setFlash("error", "Die angegebene BenutzerIn ist bereits als Admin eingetragen");
				}
			} else {
				Yii::app()->user->setFlash("error", "BenutzerIn \"" . $_REQUEST["username"] . "\" nicht gefunden. Hat die/derjenige sich schon einmal mit diesem Wurzelwerknamen bei Antragsgrün angemeldet?");
			}
		}
		if (AntiXSS::isTokenSet("remove")) {
			Yii::app()->db->createCommand()->delete("veranstaltungsreihen_admins", 'veranstaltungsreihe_id = :veranstaltungsreihe_id AND person_id = :person_id', array(":veranstaltungsreihe_id" => $this->veranstaltungsreihe->id, ":person_id" => AntiXSS::getTokenVal("remove")));
			Yii::app()->user->setFlash("success", "Die Admin-Rechte wurden entzogen");
			$this->veranstaltungsreihe->refresh();
		}

		$this->render('reihe_admins', array(
			"admins"  => $this->veranstaltungsreihe->admins,
			"sprache" => $this->veranstaltung->getSprache(),
			"ich"     => $ich,
			"del_url" => $this->createUrl("/admin/index/reiheAdmins", array(AntiXSS::createToken("remove") => "REMOVEID")),
			"add_url" => $this->createUrl("/admin/index/reiheAdmins")
		));
	}

	public function actionReiheVeranstaltungen($veranstaltungsreihe_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$user = Yii::app()->user;
		$ich  = Person::model()->findByAttributes(array("auth" => $user->id));

		if (AntiXSS::isTokenSet("add")) {
			$referenz = null;
			$url_vorhanden = false;
			foreach ($this->veranstaltungsreihe->veranstaltungen as $veranstaltung) {
				if ($veranstaltung->id == $_REQUEST["vorlage"]) $referenz = $veranstaltung;
				if ($veranstaltung->url_verzeichnis == $_REQUEST["url"]) $url_vorhanden = true;
			}
			if (!$referenz) Yii::app()->user->setFlash("error", "Vorlage nicht gefunden");
			elseif ($url_vorhanden) Yii::app()->user->setFlash("error", "Es existiert schon eine Veranstaltung mit dieser Adresse / diesem Verzeichnis");
			elseif (preg_match("/[^a-z0-9_-]/siu", $_REQUEST["url"])) Yii::app()->user->setFlash("error", "Die Adresse / das Verzeichnis darf nur aus Buchstaben ohne Umlauten, Zahlen und den Zeichen _ und - bestehen.");
			else {
				$veranstaltung                            = new Veranstaltung();
				$veranstaltung->veranstaltungsreihe_id    = $this->veranstaltungsreihe->id;
				$veranstaltung->name                      = $veranstaltung->name_kurz = $_REQUEST["name"];
				$veranstaltung->policy_kommentare         = $referenz->policy_kommentare;
				$veranstaltung->policy_aenderungsantraege = $referenz->policy_aenderungsantraege;
				$veranstaltung->policy_antraege           = $referenz->policy_antraege;
				$veranstaltung->policy_unterstuetzen      = $referenz->policy_unterstuetzen;
				$veranstaltung->typ                       = $referenz->typ;
				$veranstaltung->url_verzeichnis           = $_REQUEST["url"];
				$veranstaltung->admin_email               = $referenz->admin_email;
				$veranstaltung->setEinstellungen($referenz->getEinstellungen());
				if ($veranstaltung->save()) {
					Yii::app()->user->setFlash("success", "Die neue Veranstaltung wurde angelegt");
				} else {
					Yii::app()->user->setFlash("error", "Ein Fehler ist aufgetreten: " . print_r($veranstaltung->getErrors(), true));
				}
			}
			$this->veranstaltungsreihe->refresh();
		}

		if (AntiXSS::isTokenSet("set_std")) {
			$got_id = AntiXSS::getTokenVal("set_std");
			$neuer_standard = $this->veranstaltungsreihe->id;
			foreach ($this->veranstaltungsreihe->veranstaltungen as $veranstaltung) if ($veranstaltung->id == $got_id) $neuer_standard = $got_id;
			$this->veranstaltungsreihe->aktuelle_veranstaltung_id = $neuer_standard;
			$this->veranstaltungsreihe->save(false);
			$this->veranstaltungsreihe->refresh();
			Yii::app()->user->setFlash("success", "Der neue Standard wurde gesetzt.");
		}

		if (AntiXSS::isTokenSet("remove")) {
		}

		$this->render('reihe_veranstaltungen', array(
			"sprache" => $this->veranstaltung->getSprache(),
			"ich"     => $ich,
			"del_url" => $this->createUrl("/admin/index/reiheVeranstaltungen", array(AntiXSS::createToken("remove") => "REMOVEID")),
			"add_url" => $this->createUrl("/admin/index/reiheVeranstaltungen"),
			"set_std_url" => $this->createUrl("/admin/index/reiheVeranstaltungen", array(AntiXSS::createToken("set_std") => "STDID")),
		));
	}

	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$todo = array( //			array("Text anlegen", array("admin/texte/update", array())),
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

			$kommentare = AntragKommentar::model()->with(array(
				"antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
			))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
			foreach ($kommentare as $komm) {
				$todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->antrag->revision_name, array("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
			}

			/** @var AenderungsantragKommentar[] $kommentare */
			$kommentare = AenderungsantragKommentar::model()->with(array(
				"aenderungsantrag"        => array("alias" => "aenderungsantrag"),
				"aenderungsantrag.antrag" => array("alias" => "antrag", "condition" => "antrag.veranstaltung_id = " . IntVal($this->veranstaltung->id))
			))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_NICHT_FREI));
			foreach ($kommentare as $komm) {
				$todo[] = array("Kommentar prüfen: " . $komm->verfasserIn->name . " zu " . $komm->aenderungsantrag->revision_name, array("aenderungsantrag/anzeige", array("aenderungsantrag_id" => $komm->aenderungsantrag->id, "antrag_id" => $komm->aenderungsantrag->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id)));
			}

		}

		$this->render('index', array(
			"todo"    => $todo,
			"sprache" => $this->veranstaltung->getSprache()
		));
	}

}