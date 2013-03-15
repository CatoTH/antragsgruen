<?php

class AntragController extends AntragsgruenController
{

	public function actionAnzeige($veranstaltung_id, $antrag_id, $kommentar_id = 0)
	{
		$antrag_id = IntVal($antrag_id);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->with("antragKommentare", "antragKommentare.unterstuetzer")->findByPk($antrag_id);
		if (is_null($antrag)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("site/veranstaltung"));
		}
		$this->veranstaltung = $this->loadVeranstaltung($veranstaltung_id, $antrag);

		$this->layout = '//layouts/column2';

		if (AntiXSS::isTokenSet("komm_del")) {
			/** @var AntragKommentar $komm */
			$komm = AntragKommentar::model()->findByPk(AntiXSS::getTokenVal("komm_del"));
			if ($komm->antrag_id == $antrag->id && $komm->kannLoeschen(Yii::app()->user) && $komm->status == IKommentar::$STATUS_FREI) {
				$komm->status = IKommentar::$STATUS_GELOESCHT;
				$komm->save();
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
			} else {
				Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
			}
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
		}

		if (AntiXSS::isTokenSet("komm_freischalten") && $kommentar_id > 0) {
			/** @var AntragKommentar $komm */
			$komm = AntragKommentar::model()->findByPk($kommentar_id);
			if ($komm->antrag_id == $antrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $antrag->veranstaltung0->isAdminCurUser()) {
				$komm->status = IKommentar::$STATUS_FREI;
				$komm->save();
				Yii::app()->user->setFlash("success", "Der Kommentar wurde freigeschaltet.");
			} else {
				Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
			}
		}

		if (AntiXSS::isTokenSet("komm_nicht_freischalten") && $kommentar_id > 0) {
			/** @var AntragKommentar $komm */
			$komm = AntragKommentar::model()->findByPk($kommentar_id);
			if ($komm->antrag_id == $antrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $antrag->veranstaltung0->isAdminCurUser()) {
				$komm->status = IKommentar::$STATUS_GELOESCHT;
				$komm->save();
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
			} else {
				Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
			}
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
		}

		if (AntiXSS::isTokenSet("komm_dafuer") && $this->veranstaltung->kommentare_unterstuetzbar) {
			$meine_unterstuetzung = AntragKommentarUnterstuetzer::meineUnterstuetzung($kommentar_id);
			if ($meine_unterstuetzung === null) {
				$unterstuetzung = new AntragKommentarUnterstuetzer();
				$unterstuetzung->setIdentityParams();
				$unterstuetzung->dafuer = 1;
				$unterstuetzung->antrag_kommentar_id = $kommentar_id;

				if ($unterstuetzung->save()) Yii::app()->user->setFlash("success", "Du hast den Kommentar positiv bewertet.");
				else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
				$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
			}
		}
		if (AntiXSS::isTokenSet("komm_dagegen") && $this->veranstaltung->kommentare_unterstuetzbar) {
			$meine_unterstuetzung = AntragKommentarUnterstuetzer::meineUnterstuetzung($kommentar_id);
			if ($meine_unterstuetzung === null) {
				$unterstuetzung = new AntragKommentarUnterstuetzer();
				$unterstuetzung->setIdentityParams();
				$unterstuetzung->dafuer = 0;
				$unterstuetzung->antrag_kommentar_id = $kommentar_id;
				if ($unterstuetzung->save()) Yii::app()->user->setFlash("success", "Du hast den Kommentar negativ bewertet.");
				else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
				$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
			}
		}
		if (AntiXSS::isTokenSet("komm_dochnicht") && $this->veranstaltung->kommentare_unterstuetzbar) {
			$meine_unterstuetzung = AntragKommentarUnterstuetzer::meineUnterstuetzung($kommentar_id);
			if ($meine_unterstuetzung !== null) {
				$meine_unterstuetzung->delete();
				Yii::app()->user->setFlash("success", "Du hast die Bewertung des Kommentars zurückgenommen.");
				$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar_id, "#" => "komm" . $kommentar_id)));
			}
		}


		if (AntiXSS::isTokenSet("mag") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			$unt                   = new AntragUnterstuetzer();
			$unt->antrag_id        = $antrag->id;
			$unt->unterstuetzer_id = $userid;
			$unt->rolle            = "mag";
			$unt->kommentar        = "";
			if ($unt->save()) Yii::app()->user->setFlash("success", "Du unterstützt diesen Antrag nun.");
			else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
		}

		if (AntiXSS::isTokenSet("magnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			$unt                   = new AntragUnterstuetzer();
			$unt->antrag_id        = $antrag->id;
			$unt->unterstuetzer_id = $userid;
			$unt->rolle            = "magnicht";
			$unt->kommentar        = "";
			$unt->save();
			if ($unt->save()) Yii::app()->user->setFlash("success", "Du lehnst diesen Antrag nun ab.");
			else Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
		}

		if (AntiXSS::isTokenSet("dochnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAntragSubmit()) {
			$userid = Yii::app()->user->getState("person_id");
			foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->unterstuetzer_id == $userid) $unt->delete();
			Yii::app()->user->setFlash("success", "Du stehst diesem Antrag wieder neutral gegenüber.");
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
		}

		/** @var $antragstellerinnen array|Person[] $antragstellerinnen */
		$antragstellerinnen = array();
		$unterstuetzerinnen = array();
		$zustimmung_von     = array();
		$ablehnung_von      = array();
		if (count($antrag->antragUnterstuetzer) > 0) foreach ($antrag->antragUnterstuetzer as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $antragstellerinnen[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzerinnen[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_MAG) $zustimmung_von[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_MAG_NICHT) $ablehnung_von[] = $relatedModel->unterstuetzer;
		}


		$kommentare_offen = array();

		if (AntiXSS::isTokenSet("kommentar_schreiben") && $antrag->veranstaltung0->darfEroeffnenKommentar()) {
			$zeile = IntVal($_REQUEST["absatz_nr"]);

			$person        = $_REQUEST["Person"];
			$person["typ"] = Person::$TYP_PERSON;
			$model_person  = AntragUserIdentityOAuth::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED);

			$kommentar               = new AntragKommentar();
			$kommentar->attributes   = $_REQUEST["AntragKommentar"];
			$kommentar->absatz       = $zeile;
			$kommentar->datum        = new CDbExpression('NOW()');
			$kommentar->verfasser    = $model_person;
			$kommentar->verfasser_id = $model_person->id;
			$kommentar->antrag       = $antrag;
			$kommentar->antrag_id    = $antrag_id;
			$kommentar->status       = ($this->veranstaltung->freischaltung_kommentare ? IKommentar::$STATUS_NICHT_FREI : IKommentar::$STATUS_FREI);

			$kommentare_offen[] = $zeile;

			if ($kommentar->save()) {
				$add = ($this->veranstaltung->freischaltung_kommentare ? " Er wird nach einer kurzen Prüfung freigeschaltet und damit sichtbar." : "");
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert." . $add);

				if ($this->veranstaltung->admin_email != "" && $kommentar->status == IKommentar::$STATUS_NICHT_FREI) {
					$kommentar_link = yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id, "kommentar_id" => $kommentar->id, "#" => "komm" . $kommentar->id));
					$mails = explode(",", $this->veranstaltung->admin_email);
					foreach ($mails as $mail) if (trim($mail) != "") mb_send_mail(trim($mail), "Neuer Kommentar - bitte freischalten.",
						"Es wurde ein neuer Kommentar zum Antrag \"" . $antrag->name . "\" verfasst (nur eingeloggt sichtbar):\n" .
							"Link: " . $kommentar_link,
						"From: " . Yii::app()->params['mail_from']
					);
				}

				$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id, "kommentar_id" => $kommentar->id, "#" => "komm" . $kommentar->id)));
			} else {
				foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
			}
		}
		if ($kommentar_id > 0) {
			$abs = $antrag->getParagraphs();
			foreach ($abs as $ab) {
				/** @var AntragAbsatz $ab */
				foreach ($ab->kommentare as $komm) if ($komm->id == $kommentar_id) $kommentare_offen[] = $ab->absatz_nr;
			}
		}

		$aenderungsantraege = array();
		foreach ($antrag->aenderungsantraege as $antr) if (!in_array($antr->status, IAntrag::$STATI_UNSICHTBAR)) $aenderungsantraege[] = $antr;

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
			foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->unterstuetzer->id == Yii::app()->user->getState("person_id")) $support_status = $unt->rolle;
		}

		$this->render("anzeige", array(
			"antrag"             => $antrag,
			"antragstellerinnen" => $antragstellerinnen,
			"unterstuetzerinnen" => $unterstuetzerinnen,
			"zustimmung_von"     => $zustimmung_von,
			"ablehnung_von"      => $ablehnung_von,
			"aenderungsantraege" => $aenderungsantraege,
			"edit_link"          => $antrag->binInitiatorIn(),
			"kommentare_offen"   => $kommentare_offen,
			"kommentar_person"   => $kommentar_person,
			"admin_edit"         => (Yii::app()->user->getState("role") == "admin" ? "/admin/antraege/update/id/" . $antrag_id : null),
			"komm_del_link"      => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id, AntiXSS::createToken("komm_del") => "#komm_id#")),
			"hiddens"            => $hiddens,
			"js_protection"      => $js_protection,
			"support_status"     => $support_status,
			"sprache"            => $antrag->veranstaltung0->getSprache(),
		));
	}

	public function actionPdf($veranstaltung_id, $antrag_id)
	{
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($antrag_id);
		if (is_null($antrag)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("site/veranstaltung"));
		}

		$this->veranstaltung = $this->loadVeranstaltung($veranstaltung_id, $antrag);

		$this->renderPartial("pdf", array(
			'model'   => $antrag,
			"sprache" => $antrag->veranstaltung0->getSprache(),
		));
	}


	public function actionBearbeiten($veranstaltung_id, $antrag_id)
	{
		$this->layout = '//layouts/column2';

		$antrag_id = IntVal($antrag_id);

		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($antrag_id);
		if (is_null($antrag)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("site/veranstaltung"));
		}

		$this->veranstaltung = $this->loadVeranstaltung($veranstaltung_id, $antrag);

		if (!$antrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
		}

		if (AntiXSS::isTokenSet("antrag_del")) {
			$antrag->status = Antrag::$STATUS_ZURUECKGEZOGEN;
			if ($antrag->save()) {
				Yii::app()->user->setFlash("success", "Der Antrag wurde zurückgezogen.");
				$this->redirect($this->createUrl("site/veranstaltung"));
			} else {
				Yii::app()->user->setFlash("error", "Der Antrag konnte nicht zurückgezogen werden.");
			}
		}

		$this->render("bearbeiten_start", array(
			"antrag"  => $antrag,
			"sprache" => $antrag->veranstaltung0->getSprache(),
		));
	}


	public function actionAendern($veranstaltung_id, $antrag_id)
	{
		$this->layout = '//layouts/column2';

		$antrag_id = IntVal($antrag_id);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($antrag_id);
		if (is_null($antrag)) {
			Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
			$this->redirect($this->createUrl("site/veranstaltung"));
		}

		$this->veranstaltung = $this->loadVeranstaltung($veranstaltung_id, $antrag);

		if (!$antrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
			$this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag_id)));
		}

		if (AntiXSS::isTokenSet("antragbearbeiten")) {
			$antrag->attributes        = $_REQUEST["Antrag"];
			$antrag->text              = HtmlBBcodeUtils::bbcode_normalize($antrag->text);
			$antrag->begruendung       = HtmlBBcodeUtils::bbcode_normalize($antrag->begruendung);
			$antrag->datum_einreichung = new CDbExpression('NOW()');
			if (!in_array($antrag->status, array(IAntrag::$STATUS_UNBESTAETIGT, IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) $antrag->status = IAntrag::$STATUS_UNBESTAETIGT;

			$goon = true;

			$model_unterstuetzer_int = array();
			/** @var array|AntragUnterstuetzer[] $model_unterstuetzer_obj */
			$model_unterstuetzer_obj = array();
			if (isset($_REQUEST["UnterstuetzerTyp"])) foreach ($_REQUEST["UnterstuetzerTyp"] as $key => $typ) if ($typ != "" && $_REQUEST["UnterstuetzerName"][$key] != "") {
				$name = trim($_REQUEST["UnterstuetzerName"][$key]);
				// Man soll keinen bestätigten Nutzer eintragen können, das kann der dann selbvst machen
				$p = Person::model()->findByAttributes(array("typ" => $typ, "name" => $name, "status" => Person::$STATUS_UNCONFIRMED));
				if (!$p) {
					$p                 = new Person();
					$p->name           = $name;
					$p->typ            = $typ;
					$p->angelegt_datum = new CDbExpression('NOW()');
					$p->admin          = 0;
					$p->status         = Person::$STATUS_UNCONFIRMED;
					$p->save();

				}
				$model_unterstuetzer_int[] = $p;
				$model_unterstuetzer[]     = array("typ" => $typ, "name" => $name);

				$init                      = new AntragUnterstuetzer();
				$init->rolle               = AntragUnterstuetzer::$ROLLE_UNTERSTUETZER;
				$init->unterstuetzer_id    = $p->id;
				$init->unterstuetzer       = $p;
				$init->antrag_id           = $antrag->id;
				$model_unterstuetzer_obj[] = $init;
			}

			if (!$antrag->veranstaltung0->getPolicyAntraege()->checkAntragSubmit()) {
				Yii::app()->user->setFlash("error", "Nicht genügend UnterstützerInnen");
				$goon = false;
			}

			if ($goon && $antrag->save()) {

				foreach ($antrag->antragUnterstuetzer as $unt)
					if ($unt->rolle == AntragUnterstuetzer::$ROLLE_UNTERSTUETZER && $unt->unterstuetzer->status == Person::$STATUS_UNCONFIRMED) $unt->delete();
				foreach ($model_unterstuetzer_obj as $unt) $unt->save();

				$this->redirect($this->createUrl("antrag/neuConfirm", array("antrag_id" => $antrag_id, "next_status" => $_REQUEST["Antrag"]["status"], "from_mode" => "aendern")));
			} else {
				foreach ($antrag->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Antrag konnte nicht geändert werden: $key: " . $val2);
			}

		}

		$hiddens = array();

		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("antragbearbeiten");
		} else {
			$hiddens[AntiXSS::createToken("antragbearbeiten")] = "1";
		}

		$antragstellerin     = null;
		$model_unterstuetzer = array();

		foreach ($antrag->antragUnterstuetzer as $unt) {
			if ($unt->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $antragstellerin = $unt->unterstuetzer;
			if ($unt->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $model_unterstuetzer[] = $unt->unterstuetzer;
		}

		$this->render('bearbeiten_form', array(
			"mode"                => "bearbeiten",
			"model"               => $antrag,
			"hiddens"             => $hiddens,
			"antragstellerin"     => $antragstellerin,
			"model_unterstuetzer" => $model_unterstuetzer,
			"veranstaltung"       => $antrag->veranstaltung0,
			"js_protection"       => $js_protection,
			"login_warnung"       => Yii::app()->user->isGuest,
			"sprache"             => $antrag->veranstaltung0->getSprache(),
		));


	}


	public function actionNeuConfirm($veranstaltung_id, $antrag_id)
	{
		$this->layout = '//layouts/column2';

		$antrag_id = IntVal($antrag_id);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByAttributes(array("id" => $antrag_id, "status" => Antrag::$STATUS_UNBESTAETIGT));

		if (is_null($antrag)) {
			Yii::app()->user->setFlash("error", "Antrag nicht gefunden oder bereits bestätigt.");
			$this->redirect($this->createUrl("site/veranstaltung", array("veranstaltung_id" => $veranstaltung_id)));
		}

		$this->veranstaltung = $this->loadVeranstaltung($veranstaltung_id, $antrag);

		if (AntiXSS::isTokenSet("antragbestaetigen")) {

			$freischaltung = $antrag->veranstaltung0->freischaltung_antraege;
			$antrag->status = ($freischaltung ? Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT : Antrag::$STATUS_EINGEREICHT_GEPRUEFT);
			if (!$freischaltung && $antrag->revision_name == "") {
				$antrag->revision_name = $antrag->veranstaltung0->naechsteAntragRevNr($antrag->typ);
			}
			$antrag->save();

			if ($antrag->veranstaltung0->admin_email != "") {
				$mails = explode(",", $antrag->veranstaltung0->admin_email);
				foreach ($mails as $mail) if (trim($mail) != "") mb_send_mail(trim($mail), "Neuer Antrag",
					"Es wurde ein neuer Antrag \"" . $antrag->name . "\" eingereicht.\n" .
						"Link: " . yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
					"From: " . Yii::app()->params['mail_from']
				);
			}

			$this->render("neu_submitted", array(
				"antrag" => $antrag,
				"sprache" => $antrag->veranstaltung0->getSprache(),
			));

		} else {

			$model_unterstuetzer = array();
			for ($i = 0; $i < 15; $i++) $model_unterstuetzer[] = array("typ" => Person::$TYP_PERSON, "name" => "");

			$this->render('neu_confirm', array(
				"antrag"              => $antrag,
				"model_unterstuetzer" => $model_unterstuetzer,
				"sprache"             => $antrag->veranstaltung0->getSprache(),
			));

		}

	}

	/**
	 * @param string $veranstaltung_id
	 */
	public function actionNeu($veranstaltung_id)
	{
		$this->layout = '//layouts/column2';

		$model         = new Antrag();
		$model->status = Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
		$model->typ    = Antrag::$TYP_ANTRAG;

		/** @var Veranstaltung $veranstaltung */
		$this->veranstaltung = $veranstaltung         = $this->loadVeranstaltung($veranstaltung_id);
		$model->veranstaltung  = $veranstaltung->id;
		$model->veranstaltung0 = $veranstaltung;

		if (!$veranstaltung->getPolicyAntraege()->checkCurUserHeuristically()) {
			Yii::app()->user->setFlash("error", "Es kann kein Antrag angelegt werden.");
			$this->redirect($this->createUrl("site/veranstaltung"));
		}

		$model_unterstuetzer = array();

		if (AntiXSS::isTokenSet("antragneu")) {
			$model->attributes        = $_REQUEST["Antrag"];
			$model->text              = HtmlBBcodeUtils::bbcode_normalize($model->text);
			$model->begruendung       = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
			$model->datum_einreichung = new CDbExpression('NOW()');
			$model->status            = Antrag::$STATUS_UNBESTAETIGT;
			$goon                     = true;

			$antragstellerin = AntragUserIdentityOAuth::getCurrenPersonOrCreateBySubmitData($_REQUEST["Person"], Person::$STATUS_UNCONFIRMED);
			if (!$antragstellerin) $goon = false;

			$model_unterstuetzer_int = array();
			/** @var array|AntragUnterstuetzer[] $model_unterstuetzer_obj */
			$model_unterstuetzer_obj = array();
			if (isset($_REQUEST["UnterstuetzerTyp"])) foreach ($_REQUEST["UnterstuetzerTyp"] as $key => $typ) if ($typ != "" && $_REQUEST["UnterstuetzerName"][$key] != "") {
				$name = trim($_REQUEST["UnterstuetzerName"][$key]);
				// Man soll keinen bestätigten Nutzer eintragen können, das kann der dann selbvst machen
				$p = Person::model()->findByAttributes(array("typ" => $typ, "name" => $name, "status" => Person::$STATUS_UNCONFIRMED));
				if (!$p) {
					$p                 = new Person();
					$p->name           = $name;
					$p->typ            = $typ;
					$p->angelegt_datum = new CDbExpression('NOW()');
					$p->admin          = 0;
					$p->status         = Person::$STATUS_UNCONFIRMED;
					$p->save();

				}
				$model_unterstuetzer_int[] = $p;
				$model_unterstuetzer[]     = array("typ" => $typ, "name" => $name);

				$init                      = new AntragUnterstuetzer();
				$init->rolle               = AntragUnterstuetzer::$ROLLE_UNTERSTUETZER;
				$init->unterstuetzer_id    = $p->id;
				$init->unterstuetzer       = $p;
				$model_unterstuetzer_obj[] = $init;
			}

			$initiator                   = new AntragUnterstuetzer();
			$initiator->antrag           = $model;
			$initiator->rolle            = AntragUnterstuetzer::$ROLLE_INITIATOR;
			$initiator->unterstuetzer_id = $antragstellerin->id;
			$initiator->unterstuetzer    = $antragstellerin;

			if (!$this->veranstaltung->getPolicyAntraege()->checkAntragSubmit()) {
				Yii::app()->user->setFlash("error", "Keine Berechtigung zum Anlegen von Anträgen.");
				$goon = false;
			}

			if ($goon) {
				if ($model->save()) {
					$initiator->antrag_id = $model->id;
					if (!$initiator->save()) {
						foreach ($initiator->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Initiator konnte nicht angelegt werden: $key: " . $val2);
					}

					foreach ($model_unterstuetzer_obj as $unterst) {
						$unterst->antrag_id = $model->id;
						$unterst->save();
					}


					$this->redirect($this->createUrl("antrag/neuConfirm", array("antrag_id" => $model->id, "next_status" => $_REQUEST["Antrag"]["status"], "from_mode" => "neu")));
				} else {
					foreach ($model->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Antrag konnte nicht angelegt werden: $key: " . $val2);
				}
			}
		} else {

			if (Yii::app()->user->isGuest) {
				$antragstellerin      = new Person();
				$antragstellerin->typ = Person::$TYP_PERSON;
			} else {
				$antragstellerin = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			}
		}

		$hiddens       = array();
		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("antragneu");
		} else {
			$hiddens[AntiXSS::createToken("antragneu")] = "1";
		}

		$this->render('bearbeiten_form', array(
			"model"               => $model,
			"antragstellerin"     => $antragstellerin,
			"model_unterstuetzer" => $model_unterstuetzer,
			"veranstaltung"       => $veranstaltung,
			"hiddens"             => $hiddens,
			"js_protection"       => $js_protection,
			"login_warnung"       => Yii::app()->user->isGuest,
			"sprache"             => $model->veranstaltung0->getSprache(),
		));
	}

} 