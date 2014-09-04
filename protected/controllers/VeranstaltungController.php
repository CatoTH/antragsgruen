<?php

class VeranstaltungController extends AntragsgruenController
{

	public $text_comments = false;

	public $layout = '//layouts/column2';

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->layout = '//layouts/column2';

		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);

		if (isset($_REQUEST["login"])) $this->performLogin($this->createUrl("veranstaltung/index"));
		$this->testeWartungsmodus();

		$veranstaltung = $this->actionVeranstaltung_loadData();

		$antraege_sorted = $veranstaltung->antraegeSortiert();

		/** @var null|Person $ich */
		if (Yii::app()->user->isGuest) $ich = null;
		else {
			$ich = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}

		$neueste_aenderungsantraege = Aenderungsantrag::holeNeueste($this->veranstaltung->id, 5);
		$neueste_antraege           = Antrag::holeNeueste($this->veranstaltung->id, 5);
		$neueste_kommentare         = AntragKommentar::holeNeueste($this->veranstaltung->id, 3);

		$meine_antraege           = array();
		$meine_aenderungsantraege = array();

		if ($ich) {
			$oCriteria        = new CDbCriteria();
			$oCriteria->alias = "antrag_unterstuetzerInnen";
			$oCriteria->join  = "JOIN `antrag` ON `antrag`.`id` = `antrag_unterstuetzerInnen`.`antrag_id`";
			$oCriteria->addCondition("`antrag`.`veranstaltung_id` = " . IntVal($this->veranstaltung->id));
			$oCriteria->addCondition("`antrag_unterstuetzerInnen`.`unterstuetzerIn_id` = " . IntVal($ich->id));
			$oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->order = '`datum_einreichung` DESC';
			$dataProvider     = new CActiveDataProvider('AntragUnterstuetzerInnen', array(
				'criteria' => $oCriteria,
			));
			$meine_antraege   = $dataProvider->data;

			$oCriteria        = new CDbCriteria();
			$oCriteria->alias = "aenderungsantrag_unterstuetzerInnen";
			$oCriteria->join  = "JOIN `aenderungsantrag` ON `aenderungsantrag`.`id` = `aenderungsantrag_unterstuetzerInnen`.`aenderungsantrag_id`";
			$oCriteria->join .= " JOIN `antrag` ON `aenderungsantrag`.`antrag_id` = `antrag`.`id`";
			$oCriteria->addCondition("`antrag`.`veranstaltung_id` = " . IntVal($this->veranstaltung->id));
			$oCriteria->addCondition("`aenderungsantrag_unterstuetzerInnen`.`unterstuetzerIn_id` = " . IntVal($ich->id));
			$oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->addCondition("`aenderungsantrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->order         = '`aenderungsantrag`.`datum_einreichung` DESC';
			$dataProvider             = new CActiveDataProvider('AenderungsantragUnterstuetzerInnen', array(
				'criteria' => $oCriteria,
			));
			$meine_aenderungsantraege = $dataProvider->data;
		}

		$einleitungstext = $veranstaltung->getStandardtext("startseite");

		$this->render('index', array(
			"veranstaltung"              => $veranstaltung,
			"einleitungstext"            => $einleitungstext,
			"antraege"                   => $antraege_sorted,
			"ich"                        => $ich,
			"neueste_antraege"           => $neueste_antraege,
			"neueste_kommentare"         => $neueste_kommentare,
			"neueste_aenderungsantraege" => $neueste_aenderungsantraege,
			"meine_antraege"             => $meine_antraege,
			"meine_aenderungsantraege"   => $meine_aenderungsantraege,
			"sprache"                    => $veranstaltung->getSprache(),
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionImpressum($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->render('content', array(
			"title"            => "Impressum",
			"breadcrumb_title" => "Impressum",
			"text"             => $this->veranstaltung->getStandardtext("impressum"),
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionWartungsmodus($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->render('content', array(
			"title"            => "Wartungsmodus",
			"breadcrumb_title" => "Wartungsmodus",
			"text"             => $this->veranstaltung->getStandardtext("wartungsmodus"),
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionHilfe($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$this->render('content', array(
			"title"            => "Hilfe",
			"breadcrumb_title" => "Hilfe",
			"text"             => $this->veranstaltung->getStandardtext("hilfe"),
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionPdfs($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$antraege = $this->veranstaltung->antraegeSortiert();
		$this->renderPartial('veranstaltung_pdfs', array(
			"sprache"       => $this->veranstaltung->getSprache(),
			"antraege"      => $antraege,
			"veranstaltung" => $this->veranstaltung,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionAenderungsantragsPdfs($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$criteria        = new CDbCriteria();
		$criteria->alias = "aenderungsantrag";
		$criteria->order = "LPAD(REPLACE(aenderungsantrag.revision_name, 'Ä', ''), 3, '0')";
		$criteria->addNotInCondition("aenderungsantrag.status", IAntrag::$STATI_UNSICHTBAR);
        $criteria->addNotInCondition("antrag.status", IAntrag::$STATI_UNSICHTBAR);
		$aenderungsantraege = Aenderungsantrag::model()->with(array(
			"antrag" => array('condition' => 'antrag.veranstaltung_id=' . IntVal($this->veranstaltung->id))
		))->findAll($criteria);

		$this->renderPartial('veranstaltung_ae_pdfs', array(
			"sprache"            => $this->veranstaltung->getSprache(),
			"aenderungsantraege" => $aenderungsantraege,
			"veranstaltung"      => $this->veranstaltung,
		));
	}

	public function actionAjaxEmailIstRegistriert($veranstaltungsreihe_id = "", $veranstaltung_id = "", $email)
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$person = Person::model()->findAll(array(
			"condition" => "email='" . addslashes($email) . "' AND pwd_enc != ''"
		));
		if (count($person) > 0) {
			/** @var Person $p */
			$p = $person[0];
			if ($p->email_bestaetigt) echo "1";
			else echo "0";
		} else {
			echo "-1";
		}
	}


	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $code
	 */
	public function actionBenachrichtigungenAbmelden($veranstaltungsreihe_id = "", $code = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id);

		$x = explode("-", $code);
		if (count($x) != 2) $this->render("error", array(
			"code" => 400,
			"message" => "Ungültiger Aufruf",
		));

		/** @var Person $person */
		$person = Person::model()->findByPk($x[0]);
		if ($person->getBenachrichtigungAbmeldenCode() != $code) $this->render("error", array(
			"code" => 400,
			"message" => "Ungültiger Aufruf. Vielleicht hast du den Link aus der Benachrichtigungsmail nicht vollständig kopiert?",
		));

		if (AntiXSS::isTokenSet("abmelden")) {
			foreach ($person->veranstaltungsreihenAbos as $abo) $abo->delete();
			$this->render("benachrichtigungen_abgemeldet");
		} else {
			$this->render("benachrichtigungen_abmelden", array(
				"person" => $person
			));
		}
	}

	public function actionAnmeldungBestaetigen($veranstaltungsreihe_id = "", $email = "", $code = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id);

		$msg_error = "";

		if (isset($_REQUEST["code"])) $code = $_REQUEST["code"];
		if ($email != "" && $code != "") {
			/** @var Person $p */
			$p = Person::model()->findByAttributes(array("auth" => "email:" . $email));
			if (!$p) $msg_error = "Es existiert kein Zugang mit der angegebenen E-Mail-Adresse...?";
			else {
				if ($p->checkEmailBestaetigungsCode($code)) {
					$p->email_bestaetigt = 1;
					if ($p->save()) {
						$identity = new AntragUserIdentityPasswd($p->email, $p->auth);
						Yii::app()->user->login($identity);
						$this->render("anmeldungBestaetigt");
						Yii::app()->end();
					}
				} else $msg_error = "Der angegebene Code stimmt leider nicht.";
			}
		}

		$this->render("anmeldungBestaetigen", array(
			"email" => $email,
			"errors" => $msg_error,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $code
	 */
	public function actionBenachrichtigungen($veranstaltungsreihe_id = "", $code = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id);
		$this->testeWartungsmodus();

		$user = Yii::app()->getUser();

		$msg_ok                 = $msg_err = "";
		$aktuelle_einstellungen = null;

		/** @var Person|null $correct_person */
		$correct_person         = null;

		if (AntiXSS::isTokenSet("speichern") && !yii::app()->user->isGuest) {
			/** @var Person $ich */
			$ich  = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			$vabo = null;
			foreach ($ich->veranstaltungsreihenAbos as $abo) if ($abo->veranstaltungsreihe_id == $this->veranstaltungsreihe->id) $vabo = $abo;
			if (!$vabo) {
				$vabo                         = new VeranstaltungsreihenAbo();
				$vabo->veranstaltungsreihe_id = $this->veranstaltungsreihe->id;
				$vabo->person_id              = $ich->id;
			}
			$vabo->antraege           = isset($_REQUEST["Reihe"][$this->veranstaltung->veranstaltungsreihe_id]["antraege"]);
			$vabo->aenderungsantraege = isset($_REQUEST["Reihe"][$this->veranstaltung->veranstaltungsreihe_id]["aenderungsantraege"]);
			$vabo->kommentare         = isset($_REQUEST["Reihe"][$this->veranstaltung->veranstaltungsreihe_id]["kommentare"]);
			$vabo->save();
			$msg_ok = "Die Einstellungen wurden gespeichert.";
		}

		if ($code != "") {
			$x = explode("-", $code);
			/** @var Person $person */
			$person = Person::model()->findByPk($x[0]);
			if (!$person) $msg_err = "Diese Seite existiert nicht. Vielleicht wurde der Bestätigungslink falsch kopiert?";
			elseif ($person->email_bestaetigt) $msg_err = "Dieser Account wurde bereits bestätigt.";
			elseif (!$person->checkEmailBestaetigungsCode($code)) $msg_err = "Diese Seite existiert nicht. Vielleicht wurde der Bestätigungslink falsch kopiert? (Beachte, dass der Link in der E-Mail nur 2-3 Tage lang gültig ist.";
			else {
				$person->email_bestaetigt = 1;
				$person->save();
				$msg_ok = "Der Zugang wurde bestätigt. Ab jetzt erhältst du Benachrichtigungen per E-Mail, wenn du das so eingestellt hast.";
				$identity = new AntragUserIdentityPasswd($person->email, $person->auth);
				Yii::app()->user->login($identity);
			}
		} elseif (AntiXSS::isTokenSet("anmelden")) {
			list($correct_person, $msg_ok, $msg_err) = $this->loginOderRegistrieren_backend($_REQUEST["email"],
				(isset($_REQUEST["password"]) ? $_REQUEST["password"] : null),
				(isset($_REQUEST["bestaetigungscode"]) ? $_REQUEST["bestaetigungscode"] : null)
			);
		}

		if ($correct_person) {
			$bisherig = VeranstaltungsreihenAbo::model()->findByAttributes(array("veranstaltungsreihe_id" => $this->veranstaltungsreihe->id, "person_id" => $correct_person->id));
			if (!$bisherig) {
				$bisherig                         = new VeranstaltungsreihenAbo();
				$bisherig->person_id              = $correct_person->id;
				$bisherig->veranstaltungsreihe_id = $this->veranstaltungsreihe->id;
			}
			$bisherig->antraege           = isset($_REQUEST["Reihe"][$this->veranstaltung->id]["antraege"]);
			$bisherig->aenderungsantraege = isset($_REQUEST["Reihe"][$this->veranstaltung->id]["aenderungsantraege"]);
			$bisherig->kommentare         = isset($_REQUEST["Reihe"][$this->veranstaltung->id]["kommentare"]);
			$bisherig->save();
			$msg_ok = "Die Einstellungen wurden gespeichert.";
		}

		if ($user->isGuest) {
			$ich              = null;
			$eingeloggt       = false;
			$email_angegeben  = false;
			$email_bestaetigt = false;
		} else {
			$eingeloggt = true;
			/** @var Person $ich */
			$ich = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			if ($ich->email == "") {
				$email_angegeben  = false;
				$email_bestaetigt = false;
			} elseif ($ich->email_bestaetigt) {
				$email_angegeben  = true;
				$email_bestaetigt = true;
			} else {
				$email_angegeben  = true;
				$email_bestaetigt = false;
			}

			foreach ($ich->veranstaltungsreihenAbos as $abo) if ($abo->veranstaltungsreihe_id = $this->veranstaltungsreihe->id) $aktuelle_einstellungen = $abo;
		}

		$this->render('benachrichtigungen', array(
			"eingeloggt"             => $eingeloggt,
			"email_angegeben"        => $email_angegeben,
			"email_bestaetigt"       => $email_bestaetigt,
			"aktuelle_einstellungen" => $aktuelle_einstellungen,
			"ich"                    => $ich,
			"msg_err"                => $msg_err,
			"msg_ok"                 => $msg_ok,
		));
	}

	/**
	 * @param Veranstaltung $veranstaltung
	 * @return array
	 */
	private function getFeedAntraegeData(&$veranstaltung)
	{
		$veranstaltung_id = IntVal($veranstaltung->id);

		$antraege = Antrag::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_sql2timestamp($ant->datum_einreichung) . "_antrag_" . $ant->id] = array(
			"title"       => "Neuer Antrag: " . $ant->nameMitRev(),
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $ant->id)),
			"dateCreated" => AntraegeUtils::date_sql2timestamp($ant->datum_einreichung),
			"content"     => "<h2>Antrag</h2>" . HtmlBBcodeUtils::bbcode2html($ant->text) . "<br>\n<br>\n<br>\n<h2>Begründung</h2>" . HtmlBBcodeUtils::bbcode2html($ant->begruendung),
		);
		return $data;
	}

	/**
	 * @param Veranstaltung $veranstaltung
	 * @return array
	 */
	private function getFeedAenderungsantraegeData(&$veranstaltung)
	{
		$veranstaltung_id = IntVal($veranstaltung->id);

		$antraege = Aenderungsantrag::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_sql2timestamp($ant->datum_einreichung) . "_aenderungsantrag_" . $ant->id] = array(
			"title"       => "Neuer Änderungsantrag: " . $ant->revision_name . " zu " . $ant->antrag->nameMitRev(),
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $ant->antrag->id, "aenderungsantrag_id" => $ant->id)),
			"dateCreated" => AntraegeUtils::date_sql2timestamp($ant->datum_einreichung),
			"content"     => "<h2>Antrag</h2>" . HtmlBBcodeUtils::bbcode2html($ant->aenderung_text) . "<br>\n<br>\n<br>\n<h2>Begründung</h2>" . HtmlBBcodeUtils::bbcode2html($ant->aenderung_begruendung),
		);
		return $data;
	}

	/**
	 * @param Veranstaltung $veranstaltung
	 * @return array
	 */
	private function getFeedAntragKommentarData(&$veranstaltung)
	{
		$veranstaltung_id = IntVal($veranstaltung->id);

		$antraege = AntragKommentar::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_sql2timestamp($ant->datum) . "_kommentar_" . $ant->id] = array(
			"title"       => "Kommentar von " . $ant->verfasserIn->name . " zu: " . $ant->antrag->nameMitRev(),
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $ant->antrag->id, "kommentar_id" => $ant->id, "#" => "komm" . $ant->id)),
			"dateCreated" => AntraegeUtils::date_sql2timestamp($ant->datum),
			"content"     => HtmlBBcodeUtils::bbcode2html($ant->text),
		);
		return $data;
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionFeedAntraege($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$sprache = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Anträge"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_antraege")),
			"data"             => $this->getFeedAntraegeData($veranstaltung),
			"sprache"          => $sprache,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionFeedAenderungsantraege($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$sprache = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Änderungsanträge"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_aenderungsantraege")),
			"data"             => $this->getFeedAenderungsantraegeData($veranstaltung),
			"sprache"          => $sprache,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionFeedKommentare($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$sprache = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Kommentare"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_kommentare")),
			"data"             => $this->getFeedAntragKommentarData($veranstaltung),
			"sprache"          => $veranstaltung->getSprache(),
		));
	}


	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionFeedAlles($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$sprache = $veranstaltung->getSprache();

		$data1 = $this->getFeedAntraegeData($veranstaltung);
		$data2 = $this->getFeedAenderungsantraegeData($veranstaltung);
		$data3 = $this->getFeedAntragKommentarData($veranstaltung);

		$data = array_merge($data1, $data2, $data3);
		krsort($data);

		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => "Anträge, Änderungsanträge und Kommentare",
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_alles")),
			"data"             => $data,
			"sprache"          => $veranstaltung->getSprache(),
		));

	}


	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionSuche($veranstaltungsreihe_id = "", $veranstaltung_id = "")
	{
		$this->layout = '//layouts/column2';

		$veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		$this->testeWartungsmodus();

		$neueste_aenderungsantraege = Aenderungsantrag::holeNeueste($veranstaltung->id, 5);
		$neueste_antraege           = Antrag::holeNeueste($veranstaltung->id, 5);
		$neueste_kommentare         = AntragKommentar::holeNeueste($veranstaltung->id, 3);

		$suchbegriff        = $_REQUEST["suchbegriff"];
		$antraege           = Antrag::suche($veranstaltung->id, $suchbegriff);
		$aenderungsantraege = Aenderungsantrag::suche($veranstaltung->id, $suchbegriff);

		$this->render('suche', array(
			"veranstaltung"              => $veranstaltung,
			"neueste_antraege"           => $neueste_antraege,
			"neueste_kommentare"         => $neueste_kommentare,
			"neueste_aenderungsantraege" => $neueste_aenderungsantraege,
			"suche_antraege"             => $antraege,
			"suche_aenderungsantraege"   => $aenderungsantraege,
			"suchbegriff"                => $suchbegriff,
			"sprache"                    => $veranstaltung->getSprache(),
		));

	}

	/**
	 * @return Veranstaltung|null
	 */
	private function actionVeranstaltung_loadData()
	{
		/** @var Veranstaltung $veranstaltung */
		$this->veranstaltung = Veranstaltung::model()->
			with(array(
				'antraege'                    => array(
					'joinType' => "LEFT OUTER JOIN",
					'on'       => "`antraege`.`veranstaltung_id` = `t`.`id` AND `antraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
				),
				'antraege.aenderungsantraege' => array(
					'joinType' => "LEFT OUTER JOIN",
					"on"       => "`aenderungsantraege`.`antrag_id` = `antraege`.`id` AND `aenderungsantraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND `antraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
				),
			))->findByAttributes(array("id" => $this->veranstaltung->id));
		return $this->veranstaltung;
	}


	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if ($error = Yii::app()->errorHandler->error) {
			if (Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param string $back
	 */
	public function actionLogin($veranstaltungsreihe_id = "", $veranstaltung_id = "", $back = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);

		$this->layout = '//layouts/column2';

		$err_str = "";
		$model   = null;
		try {
			$model = $this->performLogin($back);
		} catch (Exception $e) {
			$err_str = $e->getMessage();
		}

		$this->render('login', array(
			"model"   => $model,
			"msg_err" => $err_str,
		));
	}


	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param string $back
	 */
	public function actionLogout($veranstaltungsreihe_id = "", $veranstaltung_id = "", $back = "")
	{
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);

		Yii::app()->user->logout();
		Yii::app()->user->setFlash("success", "Bis bald!");
		if ($back == "") $back = Yii::app()->homeUrl;
		$this->redirect($back);
	}
}
