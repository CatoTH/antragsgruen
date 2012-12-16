<?php

class AenderungsantragController extends Controller
{
	public $menus_html = null;
	public $breadcrumbs_topname = null;

	public function actionAnzeige()
	{
		$id = IntVal($_REQUEST["id"]);
		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByPk($id);

		if (is_null($aenderungsantrag)) {
			Yii::app()->user->setFlash("error", "Der angegebene Änderungsantrag wurde nicht gefunden.");
			$this->redirect("/");
		}


		$this->layout = '//layouts/column2';

		if (!$aenderungsantrag) {
			Yii::app()->user->setFlash("error", "Eine ungültige URL wurde aufgerufen");
			$this->redirect("/");
		}

		if (AntiXSS::isTokenSet("komm_del")) {
			/** @var AenderungsantragKommentar $komm */
			$komm = AenderungsantragKommentar::model()->findByPk(AntiXSS::getTokenVal("komm_del"));
			if ($komm->aenderungsantrag_id == $aenderungsantrag->id && $komm->kannLoeschen(Yii::app()->user) && $komm->status == IKommentar::$STATUS_FREI) {
				$komm->status = IKommentar::$STATUS_GELOESCHT;
				$komm->save();
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
			} else {
				Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
			}
		}
		if (AntiXSS::isTokenSet("mag") && !Yii::app()->user->isGuest) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($aenderungsantrag->aenderungsantragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			$unt                      = new AenderungsantragUnterstuetzer();
			$unt->aenderungsantrag_id = $aenderungsantrag->id;
			$unt->unterstuetzer_id    = $userid;
			$unt->rolle               = "mag";
			$unt->kommentar           = "";
			if ($unt->save()) Yii::app()->user->setFlash("success", "Du unterstützt diesen Änderungsantrag nun.");
			else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
			$this->redirect("/aenderungsantrag/anzeige/?id=" . $id);
		}

		if (AntiXSS::isTokenSet("magnicht") && !Yii::app()->user->isGuest) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($aenderungsantrag->aenderungsantragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			$unt                      = new AenderungsantragUnterstuetzer();
			$unt->aenderungsantrag_id = $aenderungsantrag->id;
			$unt->unterstuetzer_id    = $userid;
			$unt->rolle               = "magnicht";
			$unt->kommentar           = "";
			$unt->save();
			if ($unt->save()) Yii::app()->user->setFlash("success", "Du lehnst diesen Änderungsantrag nun ab.");
			else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
			$this->redirect("/aenderungsantrag/anzeige/?id=" . $id);
		}

		if (AntiXSS::isTokenSet("dochnicht") && !Yii::app()->user->isGuest) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($aenderungsantrag->aenderungsantragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			Yii::app()->user->setFlash("success", "Du stehst diesem Änderungsantrag wieder neutral gegenüber.");
			$this->redirect("/aenderungsantrag/anzeige/?id=" . $id);
		}

		$kommentare_offen = array();

		if (AntiXSS::isTokenSet("kommentar_schreiben") && $aenderungsantrag->antrag->veranstaltung0->darfEroeffnenKommentar()) {
			$zeile = IntVal($_REQUEST["absatz_nr"]);

			$person        = $_REQUEST["Person"];
			$person["typ"] = Person::$TYP_PERSON;
			$model_person  = AntragUserIdentity::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED);

			$kommentar                      = new AenderungsantragKommentar();
			$kommentar->attributes          = $_REQUEST["AenderungsantragKommentar"];
			$kommentar->absatz              = $zeile;
			$kommentar->datum               = new CDbExpression('NOW()');
			$kommentar->verfasser           = $model_person;
			$kommentar->verfasser_id        = $model_person->id;
			$kommentar->aenderungsantrag    = $aenderungsantrag;
			$kommentar->aenderungsantrag_id = $id;
			$kommentar->status              = IKommentar::$STATUS_FREI;

			$kommentare_offen[] = $zeile;

			if ($kommentar->save()) {
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert.");
				$this->redirect("/aenderungsantrag/anzeige/?id=" . $id . "&kommentar=" . $kommentar->id . "#komm" . $kommentar->id);
			} else {
				foreach ($kommentar->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
				foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
			}
		}
		if (isset($_REQUEST["kommentar"])) {
			$abs = $aenderungsantrag->getAntragstextParagraphs();
			foreach ($abs as $ab) {
				/** @var AntragAbsatz $ab */
				foreach ($ab->kommentare as $komm) if ($komm->id == $_REQUEST["kommentar"]) $kommentare_offen[] = $ab->absatz_nr;
			}
		}


		$antragstellerinnen = array();
		$unterstuetzerinnen = array();
		$zustimmung_von     = array();
		$ablehnung_von      = array();
		if (count($aenderungsantrag->aenderungsantragUnterstuetzer) > 0) foreach ($aenderungsantrag->aenderungsantragUnterstuetzer as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $antragstellerinnen[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzerinnen[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_MAG) $zustimmung_von[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_MAG_NICHT) $ablehnung_von[] = $relatedModel->unterstuetzer;
		}

		$hiddens       = array();
		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("kommentar_schreiben");
		} else {
			$hiddens[AntiXSS::createToken("kommentar_schreiben")] = "1";
		}


		if (Yii::app()->user->isGuest) $kommentar_person = new Person();
		else $kommentar_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));

		$support_status = "";
		if (!Yii::app()->user->isGuest) {
			foreach ($aenderungsantrag->aenderungsantragUnterstuetzer as $unt) if ($unt->unterstuetzer->id == Yii::app()->user->getState("person_id")) $support_status = $unt->rolle;
		}

		$this->render("anzeige", array(
			"aenderungsantrag"   => $aenderungsantrag,
			"antragstellerinnen" => $antragstellerinnen,
			"unterstuetzerinnen" => $unterstuetzerinnen,
			"zustimmung_von"     => $zustimmung_von,
			"ablehnung_von"      => $ablehnung_von,
			"edit_link"          => $aenderungsantrag->binInitiatorIn(),
			"admin_edit"         => (Yii::app()->user->getState("role") == "admin" ? "/admin/aenderungsantraege/update/id/" . $id : null),
			"kommentare_offen"   => $kommentare_offen,
			"kommentar_person"   => $kommentar_person,
			"komm_del_link"      => "/aenderungsantrag/anzeige/?id=${id}&" . AntiXSS::createToken("komm_del") . "=#komm_id#",
			"hiddens"            => $hiddens,
			"js_protection"      => $js_protection,
			"support_form"       => !Yii::app()->user->isGuest,
			"support_status"     => $support_status,
			"sprache"            => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
		));
	}

	public function actionPdf()
	{
		$id = IntVal($_REQUEST["id"]);
		/** @var Aenderungsantrag $antrag */
		$antrag = Aenderungsantrag::model()->findByPk($id);

		$this->renderPartial("pdf", array(
			'model'   => $antrag,
			"sprache" => $antrag->antrag->veranstaltung0->getSprache(),
		));
	}

	public function actionBearbeiten()
	{
		$this->layout = '//layouts/column2';

		$id = IntVal($_REQUEST["id"]);
		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByPk($id);

		if (!$aenderungsantrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Änderungsantrag");
			$this->redirect("/aenderungsantrag/anzeige/?id=$id");
		}

		if (AntiXSS::isTokenSet("ae_del")) {
			$aenderungsantrag->status = Aenderungsantrag::$STATUS_ZURUECKGEZOGEN;
			if ($aenderungsantrag->save()) {
				Yii::app()->user->setFlash("success", "Der Änderungsantrag wurde zurückgezogen.");
				$this->redirect("/antrag/anzeige/?id=" . $aenderungsantrag->antrag_id);
			} else {
				Yii::app()->user->setFlash("error", "Der Änderungsantrag konnte nicht zurückgezogen werden.");
			}
		}

		$this->render("bearbeiten_start", array(
			"aenderungsantrag" => $aenderungsantrag,
			"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
		));
	}

	public function actionAendern()
	{
		$this->layout = '//layouts/column2';

		$id = IntVal($_REQUEST["id"]);
		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByPk($id);

		if (!$aenderungsantrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Änderungsantrag");
			$this->redirect("/aenderungsantrag/anzeige/?id=$id");
		}

		if (AntiXSS::isTokenSet("antragbearbeiten")) {
			echo "Speichern";
			die();
		}

		$antrag = $aenderungsantrag->antrag;

		$hiddens = array("antrag_id" => $antrag->id);

		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("antragbearbeiten");
		} else {
			$hiddens[AntiXSS::createToken("antragbearbeiten")] = "1";
		}

		$this->render('bearbeiten_form', array(
			"mode"             => "bearbeiten",
			"antrag"           => $antrag,
			"aenderungsantrag" => $aenderungsantrag,
			"hiddens"          => $hiddens,
			"js_protection"    => $js_protection,
			"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
		));


	}


	public function actionNeuConfirm()
	{
		$this->layout = '//layouts/column2';

		/** @var Aenderungsantrag $aenderungsantrag */
		$aenderungsantrag = Aenderungsantrag::model()->findByAttributes(array("id" => $_REQUEST["id"]));
		if ($aenderungsantrag->status != Aenderungsantrag::$STATUS_UNBESTAETIGT) {
			$this->redirect("/aenderungsantrag/anzeige/?id=" . $aenderungsantrag->id);
		}

		if (AntiXSS::isTokenSet("antragbestaetigen")) {

			$freischaltung = $aenderungsantrag->antrag->veranstaltung0->freischaltung_aenderungsantraege;
			if ($freischaltung) {
				$aenderungsantrag->status = Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
			} else {
				$aenderungsantrag->status = Aenderungsantrag::$STATUS_EINGEREICHT_GEPRUEFT;
				$aenderungsantrag->revision_name = $aenderungsantrag->antrag->naechsteAenderungsRevNr();
			}
			$aenderungsantrag->save();

			if ($aenderungsantrag->antrag->veranstaltung0->admin_email != "") {
				$mails = explode(",", $aenderungsantrag->antrag->veranstaltung0->admin_email);
				foreach ($mails as $mail) if (trim($mail) != "") mb_send_mail(trim($mail), "Neuer Änderungsantrag",
					"Es wurde ein neuer Änderungsantrag zum Antrag \"" . $aenderungsantrag->antrag->name . "\" eingereicht.\n" .
					"Link: " . yii::app()->getBaseUrl(true) . "/aenderungsantrag/anzeige/?id=" . $aenderungsantrag->id,
					"From: " . Yii::app()->params['mail_from']
				);
			}

			$this->render("neu_submitted", array(
				"aenderungsantrag" => $aenderungsantrag,
				"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
			));

		} else {

			$this->render('neu_confirm', array(
				"aenderungsantrag" => $aenderungsantrag,
				"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
			));

		}

	}


	public function actionAjaxCalcDiff()
	{
		if (!isset($_REQUEST["absaetze"])) return;

		$antrag_id = IntVal($_REQUEST["antrag_id"]);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($antrag_id);

		$diffs = array();
		/** @var array|AntragAbsatz[] $pars */
		$pars = $antrag->getParagraphs();
		foreach ($_REQUEST["absaetze"] as $absatznr => $text_neu) {
			$diffs[$absatznr] = DiffUtils::renderBBCodeDiff2HTML($pars[$absatznr]->str_bbcode, $text_neu);
		}

		$this->renderPartial('ajax_diff', array("diffs" => $diffs));
	}

	public function actionNeu()
	{
		$this->layout = '//layouts/column2';

		$antrag_id = IntVal($_REQUEST["antrag_id"]);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($antrag_id);

		if (!$antrag->veranstaltung0->darfEroeffnenAenderungsAntrag()) {
			Yii::app()->user->setFlash("error", "Es kann kein Antrag Änderungsantrag werden.");
			$this->redirect("/antrag/anzeige/?id=" . $antrag->id);
		}


		$aenderungsantrag            = new Aenderungsantrag();
		$aenderungsantrag->antrag    = $antrag;
		$aenderungsantrag->antrag_id = $antrag->id;
		$aenderungsantrag->status    = Aenderungsantrag::$STATUS_UNBESTAETIGT;

		if (Yii::app()->user->isGuest) {
			$antragstellerin = null;
		} else {
			$antragstellerin = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}

		$changed = false;

		if (AntiXSS::isTokenSet("antragneu")) {

			$aenderungsantrag->name_neu              = $_REQUEST["Aenderungsantrag"]["name_neu"];
			$aenderungsantrag->aenderung_begruendung = HtmlBBcodeUtils::bbcode_normalize($_REQUEST["ae_begruendung"]);

			$orig_absaetze = $antrag->getParagraphs();
			$neue_absaetze = array();
			$neuer_text    = "";
			for ($i = 0; $i < count($orig_absaetze); $i++) {
				/** @var AntragAbsatz $abs */
				$abs = $orig_absaetze[$i];
				if (isset($_REQUEST["change_text"][$i])) {
					$abs_text          = HtmlBBcodeUtils::bbcode_normalize($_REQUEST["neu_text"][$i]);
					$neue_absaetze[$i] = $abs_text;
					$neuer_text .= $abs_text . "\n\n";
					$changed = true;
				} else {
					$neue_absaetze[$i] = "";
					$neuer_text .= $abs->str_bbcode . "\n\n";
				}
			}

			if ($changed) {
				$aenderungsantrag->setDiffParagraphs($neue_absaetze);

				$diff      = DiffUtils::getTextDiffMitZeilennummern(trim($antrag->text), trim($neuer_text));
				$diff_text = "";

				if ($aenderungsantrag->name_neu != $antrag->name) $diff_text .= "Neuer Titel des Antrags:\n[QUOTE]" . $aenderungsantrag->name_neu . "[/QUOTE]\n\n";
				$diff_text .= DiffUtils::diff2text($diff);

				$aenderungsantrag->aenderung_text    = $diff_text;
				$aenderungsantrag->datum_einreichung = new CDbExpression('NOW()');

			} else {
				Yii::app()->user->setFlash("error", "Es wurde nichts am Text geändert.");
			}

		}

		$hiddens = array("antrag_id" => $antrag->id);

		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("antragneu");
		} else {
			$hiddens[AntiXSS::createToken("antragneu")] = "1";
		}


		if ($changed) {
			if ($antragstellerin === null && isset($_REQUEST["Person"])) {
				$antragstellerin = Person::model()->findByAttributes(array("typ" => Person::$TYP_PERSON, "name" => trim($_REQUEST["Person"]["name"]), "status" => Person::$STATUS_UNCONFIRMED));
				if (!$antragstellerin) {
					$antragstellerin                 = new Person();
					$antragstellerin->attributes     = $_REQUEST["Person"];
					$antragstellerin->typ            = Person::$TYP_PERSON;
					$antragstellerin->admin          = 0;
					$antragstellerin->angelegt_datum = new CDbExpression('NOW()');
					$antragstellerin->status         = Person::$STATUS_UNCONFIRMED;
					$antragstellerin->save();
				}
			} elseif ($antragstellerin === null) {
				$antragstellerin = new Person();
			}

			if (!$aenderungsantrag->save()) {
				foreach ($aenderungsantrag->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Änderungsantrag konnte nicht angelegt werden: " . $val2);
				if ($antragstellerin === null) $antragstellerin = new Person();
				$this->render('bearbeiten_form', array(
					"mode"             => "neu",
					"antrag"           => $antrag,
					"aenderungsantrag" => $aenderungsantrag,
					"antragstellerin"  => $antragstellerin,
					"hiddens"          => $hiddens,
					"js_protection"    => $js_protection,
					"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
				));
				return;
			}

			$init                      = new AenderungsantragUnterstuetzer();
			$init->aenderungsantrag_id = $aenderungsantrag->id;
			$init->rolle               = AntragUnterstuetzer::$ROLLE_INITIATOR;
			$init->unterstuetzer_id    = $antragstellerin->id;
			if (!$init->save()) {
				var_dump($init->getErrors());
				die();
			}

			$this->redirect("/aenderungsantrag/neuConfirm/?id=" . $aenderungsantrag->id);

		} else {
			if ($antragstellerin === null) $antragstellerin = new Person();

			$aenderungsantrag->name_neu = $antrag->name;


			$this->render('bearbeiten_form', array(
				"mode"             => "neu",
				"antrag"           => $antrag,
				"aenderungsantrag" => $aenderungsantrag,
				"antragstellerin"  => $antragstellerin,
				"hiddens"          => $hiddens,
				"js_protection"    => $js_protection,
				"sprache"          => $aenderungsantrag->antrag->veranstaltung0->getSprache(),
			));
		}
	}

}
