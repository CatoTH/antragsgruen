<?php

class InfosController extends AntragsgruenController
{
	public function actionSelbstEinsetzen()
	{
		$this->layout = '//layouts/column2';

		$this->performLogin($this->createUrl("infos/neuAnlegen"));

		$reihen = Veranstaltungsreihe::getSidebarReihen();
		$this->render('selbst_einsetzen', array(
			"reihen" => $reihen
		));
	}

	public function actionImpressum()
	{
		$this->layout = '//layouts/column2';
		$reihen       = Veranstaltungsreihe::getSidebarReihen();
		$this->render('impressum', array(
			"reihen" => $reihen
		));
	}

	public function actionRechnungsverwaltung()
	{
		$this->performLogin($this->createUrl("infos/rechnungsverwaltung"));
		if (Yii::app()->user->isGuest) $this->redirect($this->createUrl("/infos/login", array("back" => $this->createUrl("infos/rechnungsverwaltung"))));
		else {
			/** @var Person $user */
			$user = Person::model()->findByAttributes(array("auth" => yii::app()->user->getId()));
			if ($user->id != Yii::app()->params['admin_user_id']) $this->redirect("/");
		}

		/** @var Veranstaltungsreihe[] $veranstaltungsreihen */
		$veranstaltungsreihen = Veranstaltungsreihe::model()->findAll(array("order" => "id"));

		if (AntiXSS::isTokenSet("rechnung_save")) {
			foreach ($veranstaltungsreihen as $reihe) {
				$einst = $reihe->getEinstellungen();
				$einst->rechnung_gestellt = (isset($_REQUEST["berechnet"][$reihe->id]));
				$einst->rechnung_bezahlt = (isset($_REQUEST["bezahlt"][$reihe->id]));
				$reihe->setEinstellungen($einst);
				if (!$reihe->save(false)) {
					var_dump($reihe->getErrors());
					die();
				}
			}
		}

		$reihen               = Veranstaltungsreihe::getSidebarReihen();
		$this->render("rechnungsverwaltung", array(
			"reihen"               => $reihen,
			"veranstaltungsreihen" => $veranstaltungsreihen
		));
	}

	public function actionNeuAnlegen()
	{
		$this->layout = '//layouts/column2';

		if (Yii::app()->user->isGuest) $this->redirect($this->createUrl("infos/selbstEinsetzen"));
		/** @var Person $user */
		$user = Person::model()->findByAttributes(array("auth" => yii::app()->user->getId()));
		if (!$user->istWurzelwerklerIn()) $this->redirect($this->createUrl("infos/selbstEinsetzen"));


		$anlegenformmodel = new CInstanzAnlegenForm();
		$error_str        = "";

		if (AntiXSS::isTokenSet("anlegen")) {
			$anlegenformmodel->setAttributes($_REQUEST["CInstanzAnlegenForm"]);

			$reihe                 = new Veranstaltungsreihe();
			$reihe->subdomain      = trim($anlegenformmodel->subdomain);
			$reihe->name           = $reihe->name_kurz = $anlegenformmodel->name;
			$reihe->offiziell      = false;
			$reihe->oeffentlich    = true;
			$reihe->kontakt_intern = $anlegenformmodel->kontakt;

			$einstellungen = $reihe->getEinstellungen();
			$einstellungen->bereit_zu_zahlen = $anlegenformmodel->zahlung;
			$reihe->setEinstellungen($einstellungen);

			$subdomain = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $reihe->subdomain));
			if ($subdomain) {
				$error_str .= "Es gibt leider bereits eine Reihe mit dieser Subdomain.<br>\n";
			} elseif ($reihe->save()) {
				$veranstaltung                         = new Veranstaltung();
				$veranstaltung->veranstaltungsreihe_id = $reihe->id;
				$veranstaltung->name                   = $veranstaltung->name_kurz = $anlegenformmodel->name;
				$veranstaltung->antragsschluss         = $anlegenformmodel->antragsschluss;
				$veranstaltung->policy_kommentare      = Veranstaltung::$POLICY_ALLE;
				$veranstaltung->policy_unterstuetzen   = "Niemand";
				$veranstaltung->typ                    = $anlegenformmodel->typ;
				$veranstaltung->url_verzeichnis        = $anlegenformmodel->subdomain;
				$veranstaltung->admin_email            = $anlegenformmodel->admin_email;
				$veranstaltung->praefix                = "Ä";
				
				$einstellungen                       = $veranstaltung->getEinstellungen();
				$einstellungen->wartungs_modus_aktiv = !$anlegenformmodel->sofort_offen;

				if ($anlegenformmodel->typ == Veranstaltung::$TYP_PROGRAMM) {
					$einstellungen->zeilen_nummerierung_global       = true;
					$einstellungen->ae_nummerierung_global           = true;
					$einstellungen->freischaltung_antraege           = false;
					$einstellungen->freischaltung_aenderungsantraege = false;

					$veranstaltung->policy_antraege           = IPolicyAntraege::$POLICY_ADMINS;
					$veranstaltung->policy_aenderungsantraege = IPolicyAntraege::$POLICY_ALLE;
				}
				if ($anlegenformmodel->typ == Veranstaltung::$TYP_PARTEITAG) {
					$einstellungen->zeilen_nummerierung_global       = false;
					$einstellungen->ae_nummerierung_global           = false;
					$einstellungen->freischaltung_antraege           = true;
					$einstellungen->freischaltung_aenderungsantraege = true;

					$veranstaltung->policy_antraege           = IPolicyAntraege::$POLICY_ALLE;
					$veranstaltung->policy_aenderungsantraege = IPolicyAntraege::$POLICY_ALLE;
				}
				$veranstaltung->setEinstellungen($einstellungen);
				if ($veranstaltung->save()) {
					$reihe->aktuelle_veranstaltung_id = $veranstaltung->id;
					$reihe->save();
					Yii::app()->db->createCommand()->insert("veranstaltungsreihen_admins", array("veranstaltungsreihe_id" => $reihe->id, "person_id" => $user->id));

					$impressum                   = new Texte();
					$impressum->edit_datum       = new CDbExpression("NOW()");
					$impressum->text_id          = "impressum";
					$impressum->veranstaltung_id = $veranstaltung->id;
					$impressum->text             = nl2br(CHtml::encode($anlegenformmodel->kontakt));
					$impressum->save();

					$impressum                   = new Texte();
					$impressum->edit_datum       = new CDbExpression("NOW()");
					$impressum->text_id          = "wartungsmodus";
					$impressum->veranstaltung_id = $veranstaltung->id;
					$impressum->text             = "Diese Veranstaltung wurde vom Admin noch nicht freigeschaltet.";
					$impressum->save();

					$login_id   = $user->id;
					$login_code = AntiXSS::createToken($login_id);

					$reihen = Veranstaltungsreihe::getSidebarReihen();
					$this->render('neu_angelegt', array(
						"reihen"     => $reihen,
						"reihe"      => $reihe,
						"login_id"   => $login_id,
						"login_code" => $login_code,
					));
					return;

				} else {
					foreach ($veranstaltung->errors as $err) foreach ($err as $e) $error_str .= $e . "<br>\n";
				}
			} else {
				foreach ($reihe->errors as $err) foreach ($err as $e) $error_str .= $e . "<br>\n";
			}
		}

		$reihen = Veranstaltungsreihe::getSidebarReihen();
		$this->render('neu_anlegen', array(
			"reihen"           => $reihen,
			"anlegenformmodel" => $anlegenformmodel,
			"error_string"     => $error_str
		));
	}


	public function actionPasswort()
	{
		$this->layout = '//layouts/column2';
		$this->performLogin($this->createUrl("veranstaltung/passwort"));

		$user = Yii::app()->getUser();
		/** @var PErson $ich */
		$ich = Person::model()->findByAttributes(array("auth" => $user->id));

		$msg_ok                 = $msg_err = "";
		$correct_person         = null;
		$aktuelle_einstellungen = null;

		if (AntiXSS::isTokenSet("speichern")) {
			if ($_REQUEST["pw_neu"] != $_REQUEST["pw_neu2"]) {
				$msg_err = "Die beiden Passwörter stimmen nicht überein.";
			} elseif (strlen(trim($_REQUEST["pw_neu"])) < 5) {
				$msg_err = "Das Passwort muss mindestens 5 Zeichen lang sein.";
			} elseif (!$ich->validate_password($_REQUEST["pw_alt"])) {
				$msg_err = "Das bisherige Passwort stimmt nicht.";
			} else {
				$ich->pwd_enc = Person::create_hash($_REQUEST["pw_neu"]);
				$ich->save();
				$msg_ok = "Das neue Passwort wurde gespeichert.";
			}
		}

		$this->render('passwort', array(
			"ich"     => $ich,
			"msg_err" => $msg_err,
			"msg_ok"  => $msg_ok,
		));
	}

	/**
	 * @param string $back
	 */
	public function actionLogin($back = "")
	{
		$this->layout = '//layouts/column2';

		$err_str = "";
		$model   = null;
		try {
			$model = $this->performLogin($back);
		} catch (Exception $e) {
			$err_str = $e->getMessage();
		}

		$this->render('/veranstaltung/login', array(
			"model"   => $model,
			"msg_err" => $err_str,
		));
	}


	/**
	 * @param string $back
	 */
	public function actionLogout($back = "")
	{
		Yii::app()->user->logout();
		Yii::app()->user->setFlash("success", "Bis bald!");
		if ($back == "") $back = Yii::app()->homeUrl;
		$this->redirect($back);
	}
}
