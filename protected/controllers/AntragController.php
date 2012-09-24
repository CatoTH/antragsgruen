<?php

class AntragController extends Controller
{
	public $menus_html = null;

	public function actionAnzeige()
	{
		$id = IntVal($_REQUEST["id"]);
		/** @var Antrag $antrag  */
		$antrag = Antrag::model()->findByPk($id);

		$this->layout = '//layouts/column2';

		if (AntiXSS::isTokenSet("komm_del")) {
			/** @var AntragKommentar $komm  */
			$komm = AntragKommentar::model()->findByPk(AntiXSS::getTokenVal("komm_del"));
			if ($komm->antrag_id == $antrag->id && $komm->kannLoeschen(Yii::app()->user) && $komm->status == IKommentar::$STATUS_FREI) {
				$komm->status = IKommentar::$STATUS_GELOESCHT;
				$komm->save();
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
			} else {
				Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
			}
		}

		/** @var $antragstellerinnen array|Person[] $antragstellerinnen  */
		$antragstellerinnen = array();
		$unterstuetzerinnen = array();
		if (count($antrag->antragUnterstuetzer) > 0) foreach ($antrag->antragUnterstuetzer as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_INITIATOR) $antragstellerinnen[] = $relatedModel->unterstuetzer;
			if ($relatedModel->rolle == IUnterstuetzer::$ROLLE_UNTERSTUETZER) $unterstuetzerinnen[] = $relatedModel->unterstuetzer;
		}


		$kommentare_offen = array();

		if (AntiXSS::isTokenSet("kommentar_absatz") && $antrag->veranstaltung0->darfEroeffnenKommentar()) {
			$zeile = AntiXSS::getTokenVal("kommentar_absatz");

			$person        = $_REQUEST["Person"];
			$person["typ"] = Person::$TYP_PERSON;
			$model_person  = AntragUserIdentity::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED);

			$kommentar               = new AntragKommentar();
			$kommentar->attributes   = $_REQUEST["AntragKommentar"];
			$kommentar->absatz       = $zeile;
			$kommentar->datum        = new CDbExpression('NOW()');
			$kommentar->verfasser    = $model_person;
			$kommentar->verfasser_id = $model_person->id;
			$kommentar->antrag       = $antrag;
			$kommentar->antrag_id    = $id;
			$kommentar->status       = IKommentar::$STATUS_FREI;

			$kommentare_offen[] = $zeile;

			if ($kommentar->save()) {
				Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert.");
				$this->redirect("/antrag/anzeige/?id=" . $id . "&kommentar=" . $kommentar->id . "#komm" . $kommentar->id);
			} else {
				foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
			}
		}
		if (isset($_REQUEST["kommentar"])) {
			$abs = $antrag->getParagraphs();
			foreach ($abs as $ab) {
				/** @var AntragAbsatz $ab */
				foreach ($ab->kommentare as $komm) if ($komm->id == $_REQUEST["kommentar"]) $kommentare_offen[] = $ab->absatz_nr;
			}
		}

		$aenderungsantraege = array();
		foreach ($antrag->aenderungsantraege as $antr) if (!in_array($antr->status, IAntrag::$STATI_UNSICHTBAR)) $aenderungsantraege[] = $antr;

		$hiddens       = array();
		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("kommentar_absatz");
		} else {
			$hiddens[AntiXSS::createToken("kommentar_absatz")] = "1";
		}

		if (Yii::app()->user->isGuest) $kommentar_person = new Person();
		else $kommentar_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));

		$this->render("anzeige", array(
			"antrag"              => $antrag,
			"antragstellerinnen"  => $antragstellerinnen,
			"unterstuetzerinnen"  => $unterstuetzerinnen,
			"aenderungsantraege"  => $aenderungsantraege,
			"edit_link"           => $antrag->binInitiatorIn(),
			"kommentare_offen"    => $kommentare_offen,
			"kommentar_person"    => $kommentar_person,
			"admin_edit"          => (Yii::app()->user->getState("role") == "admin" ? "/admin/antraege/update/id/" . $id : null),
			"komm_del_link"       => "/antrag/anzeige/?id=${id}&" . AntiXSS::createToken("komm_del") . "=#komm_id#",
			"hiddens"             => $hiddens,
			"js_protection"       => $js_protection,
		));
	}

	public function actionPdf()
	{
		$id     = IntVal($_REQUEST["id"]);
		$antrag = Antrag::model()->findByPk($id);

		$this->renderPartial("pdf", array(
			'model' => $antrag,
		));
	}


	public function actionBearbeiten()
	{
		$this->layout = '//layouts/column2';

		$id = IntVal($_REQUEST["id"]);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($id);

		if (!$antrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
			$this->redirect("/antrag/anzeige/?id=$id");
		}

		if (AntiXSS::isTokenSet("antrag_del")) {
			$antrag->status = Antrag::$STATUS_ZURUECKGEZOGEN;
			if ($antrag->save()) {
				Yii::app()->user->setFlash("success", "Der Antrag wurde zurückgezogen.");
				$this->redirect("/");
			} else {
				Yii::app()->user->setFlash("error", "Der Antrag konnte nicht zurückgezogen werden.");
			}
		}

		$this->render("bearbeiten_start", array(
			"antrag" => $antrag,
		));
	}


	public function actionAendern()
	{
		$this->layout = '//layouts/column2';

		$id = IntVal($_REQUEST["id"]);
		/** @var Antrag $antrag */
		$antrag = Antrag::model()->findByPk($id);

		if (!$antrag->binInitiatorIn()) {
			Yii::app()->user->setFlash("error", "Kein Zugriff auf den Antrag");
			$this->redirect("/antrag/anzeige/?id=$id");
		}

		if (AntiXSS::isTokenSet("antragbearbeiten")) {
			$antrag->attributes        = $_REQUEST["Antrag"];
			$antrag->text              = HtmlBBcodeUtils::bbcode_normalize($antrag->text);
			$antrag->begruendung       = HtmlBBcodeUtils::bbcode_normalize($antrag->begruendung);
			$antrag->datum_einreichung = new CDbExpression('NOW()');
			if (!in_array($antrag->status, array(IAntrag::$STATUS_UNBESTAETIGT, IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) $antrag->status = IAntrag::$STATUS_UNBESTAETIGT;

			$goon = true;

			$model_unterstuetzer_int = array();
			/** @var array|AntragUnterstuetzer[] $model_unterstuetzer_obj  */
			$model_unterstuetzer_obj = array();
			if (isset($_REQUEST["UnterstuetzerTyp"])) foreach ($_REQUEST["UnterstuetzerTyp"] as $key=> $typ) if ($typ != "" && $_REQUEST["UnterstuetzerName"][$key] != "") {
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

			$initiator = null;
			foreach ($antrag->antragUnterstuetzer as $unt) if ($unt->rolle == AntragUnterstuetzer::$ROLLE_INITIATOR) $initiator = $unt;

			if (!$antrag->veranstaltung0->getPolicyAntraege()->checkOnCreate($antrag, $initiator, $model_unterstuetzer_obj)) {
				Yii::app()->user->setFlash("error", "Nicht genügend UnterstützerInnen");
				$goon = false;
			}

			if ($goon && $antrag->save()) {

				foreach ($antrag->antragUnterstuetzer as $unt)
					if ($unt->rolle == AntragUnterstuetzer::$ROLLE_UNTERSTUETZER && $unt->unterstuetzer->status == Person::$STATUS_UNCONFIRMED) $unt->delete();
				foreach ($model_unterstuetzer_obj as $unt) $unt->save();

				$this->redirect("/antrag/neuConfirm/?id=" . $antrag->id . "&next_status=" . $_REQUEST["Antrag"]["status"] . "&from_mode=aendern");
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

		while (count($model_unterstuetzer) < 15) {
			$model_unterstuetzer[] = array("typ" => Person::$TYP_PERSON, "name" => "");
		}

		$this->render('bearbeiten_form', array(
			"mode"                     => "bearbeiten",
			"model"                    => $antrag,
			"hiddens"                  => $hiddens,
			"antragstellerin"          => $antragstellerin,
			"model_unterstuetzer"      => $model_unterstuetzer,
			"veranstaltung"            => $antrag->veranstaltung0,
			"js_protection"            => $js_protection,
		));


	}


	public function actionNeuConfirm()
	{
		$this->layout = '//layouts/column2';

		/** @var Antrag $antrag  */
		$antrag = Antrag::model()->findByAttributes(array("id" => $_REQUEST["id"], "status" => Antrag::$STATUS_UNBESTAETIGT));

		if (AntiXSS::isTokenSet("antragbestaetigen")) {

			$antrag->status = IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
			$antrag->save();

			$this->render("neu_submitted", array("antrag" => $antrag));

		} else {

			$model_unterstuetzer = array();
			for ($i = 0; $i < 15; $i++) $model_unterstuetzer[] = array("typ" => Person::$TYP_PERSON, "name" => "");

			$this->render('neu_confirm', array(
				"antrag"               => $antrag,
				"model_unterstuetzer"  => $model_unterstuetzer,
			));

		}

	}

	public function actionNeu()
	{
		$this->layout = '//layouts/column2';

		$model         = new Antrag();
		$model->status = Antrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
		$model->typ    = Antrag::$TYP_ANTRAG;


		/** @var Veranstaltung $veranstaltung  */
		$veranstaltung         = Veranstaltung::model()->findByPk($_REQUEST["veranstaltung"]);
		$model->veranstaltung  = $veranstaltung->id;
		$model->veranstaltung0 = $veranstaltung;

		if (!$veranstaltung->darfEroeffnenAntrag()) {
			Yii::app()->user->setFlash("error", "Es kann kein Antrag angelegt werden.");
			$this->redirect("/?veranstaltung=" . $veranstaltung->id);
		}

		$model_unterstuetzer = array();

		if (AntiXSS::isTokenSet("antragneu")) {
			$model->attributes        = $_REQUEST["Antrag"];
			$model->text              = HtmlBBcodeUtils::bbcode_normalize($model->text);
			$model->begruendung       = HtmlBBcodeUtils::bbcode_normalize($model->begruendung);
			$model->datum_einreichung = new CDbExpression('NOW()');
			$model->status            = Antrag::$STATUS_UNBESTAETIGT;
			$goon                     = true;

			$antragstellerin = AntragUserIdentity::getCurrenPersonOrCreateBySubmitData($_REQUEST["Person"], Person::$STATUS_UNCONFIRMED);
			if (!$antragstellerin) $goon = false;

			$model_unterstuetzer_int = array();
			/** @var array|AntragUnterstuetzer[] $model_unterstuetzer_obj  */
			$model_unterstuetzer_obj = array();
			if (isset($_REQUEST["UnterstuetzerTyp"])) foreach ($_REQUEST["UnterstuetzerTyp"] as $key=> $typ) if ($typ != "" && $_REQUEST["UnterstuetzerName"][$key] != "") {
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

			if (!$veranstaltung->getPolicyAntraege()->checkOnCreate($model, $initiator, $model_unterstuetzer_obj)) {
				Yii::app()->user->setFlash("error", "Nicht genügend UnterstützerInnen");
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


					$this->redirect("/antrag/neuConfirm/?id=" . $model->id . "&next_status=" . $_REQUEST["Antrag"]["status"] . "&from_mode=neu");
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

		for ($i = count($model_unterstuetzer); $i < $veranstaltung->getPolicyAntraege()->getStdUnterstuetzerFields(); $i++)
			$model_unterstuetzer[] = array("typ" => Person::$TYP_PERSON, "name" => "");

		$hiddens       = array();
		$js_protection = Yii::app()->user->isGuest;
		if ($js_protection) {
			$hiddens["form_token"] = AntiXSS::createToken("antragneu");
		} else {
			$hiddens[AntiXSS::createToken("antragneu")] = "1";
		}

		$this->render('bearbeiten_form', array(
			"model"                  => $model,
			"antragstellerin"        => $antragstellerin,
			"model_unterstuetzer"    => $model_unterstuetzer,
			"veranstaltung"          => $veranstaltung,
			"hiddens"                => $hiddens,
			"js_protection"          => $js_protection,
		));
	}

} 