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

	public function actionNamespacedAccounts($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		$msg = "";

		if (AntiXSS::isTokenSet("eintragen")) {
			$text = $_REQUEST["email_text"];

			$zeilen_email      = explode("\n", $_REQUEST["email_adressen"]);
			$zeilen_namen      = explode("\n", $_REQUEST["namen"]);
			$email_invalid     = array();
			$emails_verschickt = array();
			$emails_schonda    = array();

			if (count($zeilen_email) == count($zeilen_namen)) for ($zeile = 0; $zeile < count($zeilen_email); $zeile++) {
				if (trim($zeilen_email[$zeile]) == "") continue;
				$email = trim($zeilen_email[$zeile]);
				$name = trim($zeilen_namen[$zeile]);

				$valid = preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/siu", $email);
				if (!$valid) $email_invalid[] = $email;
				else {
					$schon_da = Person::model()->findByAttributes(array("veranstaltungsreihe_namespace" => $this->veranstaltungsreihe->id, "email" => $email));
					if ($schon_da) {
						$emails_schonda[] = $email;
						continue;
					}

					$password = Person::createPassword();

					$person                                = new Person();
					$person->auth                          = "ns_admin:" . $this->veranstaltungsreihe->id . ":" . $email;
					$person->name                          = $name;
					$person->email                         = $email;
					$person->email_bestaetigt              = 1;
					$person->angelegt_datum                = date("Y-m-d H:i:s");
					$person->pwd_enc                       = Person::create_hash($password);
					$person->status                        = Person::$STATUS_CONFIRMED;
					$person->typ                           = Person::$TYP_PERSON;
					$person->veranstaltungsreihe_namespace = $this->veranstaltungsreihe->id;
					if ($person->save()) {
						$link      = yii::app()->getBaseUrl(true) . $this->createUrl("veranstaltung/index");
						$mail_text = str_replace(array("%EMAIL%", "%LINK%"), array($email, $link), $text);
						$person_id = null;

						AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_NAMESPACED_ACCOUNT_ANGELEGT, $email, $person_id, "Antragsgrün-Zugang", $mail_text, null, array(
							"%PASSWORT%" => $password
						));

						$emails_verschickt[] = $email;
					} else {
						$msg .= "<div class='alert alert-danger'>Bei der E-Mail-Adresse " . CHtml::encode($email) . " ist ein Fehler aufgetreten:<br>\n";
						$msg .= CHtml::encode(print_r($person->getErrors(), true));
						$msg .= "</div>";
					}
				}
			}

			if (count($emails_verschickt) > 0) {
				$msg .= '<div class="alert alert-success" role="alert">';
				$msg .= (count($emails_verschickt) == 1 ? "1 Zugang wurde angelegt." : count($emails_verschickt) . " Zugänge wurden angelegt.");
				$msg .= '</div>';
			}
			if (count($emails_schonda) > 0) {
				$msg .= "<div class='alert alert-danger'>Folgende angegebenen E-Mail-Adressen waren bereits registriert:<br>\n";
				foreach ($emails_schonda as $inv) $msg .= "- " . CHtml::encode($inv) . "<br>\n";
				$msg .= '</div>';
			}
			if (count($email_invalid) > 0) {
				$msg .= "<div class='alert alert-danger'>Folgende angegebenen E-Mail-Adressen sind ungültig:<br>\n";
				foreach ($email_invalid as $inv) $msg .= "- " . CHtml::encode($inv) . "<br>\n";
				$msg .= '</div>';
			}
		}

		$accounts = Person::model()->findAllByAttributes(array("veranstaltungsreihe_namespace" => $this->veranstaltungsreihe->id));

		$this->render("namespaced_accounts", array(
			"accounts" => $accounts,
			"msg"      => $msg,
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

	public function actionAeExcelList($veranstaltungsreihe_id = "", $veranstaltung_id = "", $text_begruendung_zusammen = false, $antraege_separat = false, $zeilennummer_separat = false)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		ini_set('memory_limit', '256M');

		$antraege_sorted = $this->veranstaltung->antraegeSortiert();
		$antrs           = array();
		foreach ($antraege_sorted as $gruppe) foreach ($gruppe as $antr) {
			/** @var Antrag $antr */

			$antrs[] = array(
				"antrag" => $antr,
				"aes"    => $antr->aenderungsantraege
			);
		}

		$this->renderPartial("ae_excel_list", array(
			"antraege"                  => $antrs,
			"text_begruendung_zusammen" => $text_begruendung_zusammen,
			"antraege_separat"          => $antraege_separat,
			"zeilennummer_separat"      => $zeilennummer_separat
		));
	}

	public function actionAntragExcelList($veranstaltungsreihe_id = "", $veranstaltung_id = "", $text_begruendung_zusammen = false)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/site/login", array("back" => yii::app()->getRequest()->requestUri)));

		ini_set('memory_limit', '256M');

		$antraege_sorted = $this->veranstaltung->antraegeSortiert();
		$antrs           = array();
		foreach ($antraege_sorted as $gruppe) foreach ($gruppe as $antr) {
			/** @var Antrag $antr */
			$antrs[] = array(
				"antrag" => $antr,
			);
		}

		$this->renderPartial("antrag_excel_list", array(
			"antraege"                  => $antrs,
			"text_begruendung_zusammen" => $text_begruendung_zusammen
		));
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
			$referenz      = null;
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
			$got_id         = AntiXSS::getTokenVal("set_std");
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
			"sprache"     => $this->veranstaltung->getSprache(),
			"ich"         => $ich,
			"del_url"     => $this->createUrl("/admin/index/reiheVeranstaltungen", array(AntiXSS::createToken("remove") => "REMOVEID")),
			"add_url"     => $this->createUrl("/admin/index/reiheVeranstaltungen"),
			"set_std_url" => $this->createUrl("/admin/index/reiheVeranstaltungen", array(AntiXSS::createToken("set_std") => "STDID")),
		));
	}

	public function actionFullTextExport($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$antraege_sorted = $this->veranstaltung->antraegeSortiert();
		$antraege        = array();
		$aes             = array();
		foreach ($antraege_sorted as $gruppe) foreach ($gruppe as $antr) {
			$antraege[] = $antr;
			foreach ($antr->aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) $aes[] = $ae;
		}

		$this->renderPartial('full_text_export', array(
			"antraege"           => $antraege,
			"aenderungsantraege" => $aes,
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