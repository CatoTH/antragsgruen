<?php

class SiteController extends VeranstaltungsControllerBase
{

	public $text_comments = false;

	/**
	 *
	 */
	public function actionIndex($veranstaltung_id = 0)
	{
		try {
			$veranstaltung_id = ($veranstaltung_id > 0 ? IntVal($veranstaltung_id) : Yii::app()->params['standardVeranstaltung']);
			$this->actionVeranstaltung($veranstaltung_id);
		} catch (CDbException $e) {
			echo "Es konnte keine Datenbankverbindung hergestellt werden.<br>";
			if (YII_DEBUG) echo "Die Fehlermeldung lautete:<blockquote>" . $e->getMessage() . "</blockquote>";
		}
	}

	/**
	 *
	 */
	public function actionImpressum()
	{
		$this->setStdVeranstaltung();
		$this->render('content', array(
			"title"            => "Impressum",
			"breadcrumb_title" => "Impressum",
			"text"             => $this->veranstaltung->getStandardtext("impressum"),
		));
	}

	/**
	 *
	 */
	public function actionHilfe()
	{
		$this->setStdVeranstaltung();
		$this->render('content', array(
			"title"            => "Hilfe",
			"breadcrumb_title" => "Hilfe",
			"text"             => $this->veranstaltung->getStandardtext("hilfe"),
		));
	}

	/**
	 * @param Veranstaltung $veranstaltung
	 * @return array
	 */
	private function getFeedAntraegeData(&$veranstaltung)
	{
		$veranstaltung_id = IntVal($veranstaltung->id);
		if ($veranstaltung_id == 0 && isset($_REQUEST["id"])) $veranstaltung_id = IntVal($_REQUEST["id"]);

		$antraege = Antrag::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_iso2timestamp($ant->datum_einreichung) . "_antrag_" . $ant->id] = array(
			"title"       => "Neuer Antrag: " . $ant->revision_name . " - " . $ant->name,
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $ant->id)),
			"dateCreated" => AntraegeUtils::date_iso2timestamp($ant->datum_einreichung),
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
		if ($veranstaltung_id == 0 && isset($_REQUEST["id"])) $veranstaltung_id = IntVal($_REQUEST["id"]);

		$antraege = Aenderungsantrag::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_iso2timestamp($ant->datum_einreichung) . "_aenderungsantrag_" . $ant->id] = array(
			"title"       => "Neuer Änderungsantrag: " . $ant->revision_name . " zu " . $ant->antrag->revision_name . " - " . $ant->antrag->name,
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $ant->antrag->id, "aenderungsantrag_id" => $ant->id)),
			"dateCreated" => AntraegeUtils::date_iso2timestamp($ant->datum_einreichung),
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
		if ($veranstaltung_id == 0 && isset($_REQUEST["id"])) $veranstaltung_id = IntVal($_REQUEST["id"]);

		$antraege = AntragKommentar::holeNeueste($veranstaltung_id, 20);

		$data = array();
		foreach ($antraege as $ant) $data[AntraegeUtils::date_iso2timestamp($ant->datum) . "_kommentar_" . $ant->id] = array(
			"title"       => "Kommentar von " . $ant->verfasser->name . " zu: " . $ant->antrag->revision_name . " - " . $ant->antrag->name,
			"link"        => Yii::app()->getBaseUrl(true) . $this->createUrl("antrag/anzeige", array("antrag_id" => $ant->antrag->id, "kommentar_id" => $ant->id, "#" => "komm" . $ant->id)),
			"dateCreated" => AntraegeUtils::date_iso2timestamp($ant->datum),
			"content"     => HtmlBBcodeUtils::bbcode2html($ant->text),
		);
		return $data;
	}

	/**
	 * @param int $veranstaltung_id
	 */
	public function actionFeedAntraege($veranstaltung_id = 0)
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltung_id);
		$sprache       = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Anträge"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_antraege")),
			"data"             => $this->getFeedAntraegeData($veranstaltung),
			"sprache"          => $sprache,
		));
	}

	/**
	 * @param int $veranstaltung_id
	 */
	public function actionFeedAenderungsantraege($veranstaltung_id = 0)
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltung_id);
		$sprache       = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Änderungsanträge"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_aenderungsantraege")),
			"data"             => $this->getFeedAenderungsantraegeData($veranstaltung),
			"sprache"          => $sprache,
		));
	}

	/**
	 * @param int $veranstaltung_id
	 */
	public function actionFeedKommentare($veranstaltung_id = 0)
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltung_id);
		$sprache       = $veranstaltung->getSprache();
		$this->renderPartial('feed', array(
			"veranstaltung_id" => $veranstaltung->id,
			"feed_title"       => $sprache->get("Kommentare"),
			"feed_description" => str_replace("%veranstaltung%", $veranstaltung->name, $sprache->get("feed_desc_kommentare")),
			"data"             => $this->getFeedAntragKommentarData($veranstaltung),
			"sprache"          => $veranstaltung->getSprache(),
		));
	}


	/**
	 * @param int $veranstaltung_id
	 */
	public function actionFeedAlles($veranstaltung_id = 0)
	{
		$veranstaltung = $this->loadVeranstaltung($veranstaltung_id);
		$sprache       = $veranstaltung->getSprache();

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


	public function actionSuche($veranstaltung_id = 0)
	{
		$this->layout = '//layouts/column2';

		$veranstaltung = $this->loadVeranstaltung($veranstaltung_id);
		$neueste_aenderungsantraege = Aenderungsantrag::holeNeueste($veranstaltung->id, 5);
		$neueste_antraege           = Antrag::holeNeueste($veranstaltung->id, 5);
		$neueste_kommentare         = AntragKommentar::holeNeueste($veranstaltung->id, 3);

		$suchbegriff        = $_REQUEST["suchbegriff"];
		$antraege           = Antrag::suche($suchbegriff);
		$aenderungsantraege = Aenderungsantrag::suche($suchbegriff);

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
	 * @param int $veranstaltung_id
	 * @return Veranstaltung|null
	 */
	private function actionVeranstaltung_loadData($veranstaltung_id)
	{
		$att = (is_numeric($veranstaltung_id) ? "id" : "yii_url");

		/** @var Veranstaltung $veranstaltung */
		$this->veranstaltung = Veranstaltung::model()->
			with(array(
				'antraege'                    => array(
					'joinType' => "LEFT OUTER JOIN",
					'on'       => "`antraege`.`veranstaltung` = `t`.`id` AND `antraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
				),
				'antraege.aenderungsantraege' => array(
					'joinType' => "LEFT OUTER JOIN",
					"on"       => "`aenderungsantraege`.`antrag_id` = `antraege`.`id` AND `aenderungsantraege`.`status` NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ")",
				),
			))->findByAttributes(array($att => $veranstaltung_id));
		return $this->veranstaltung;
	}


	/**
	 * @param int $veranstaltung_id
	 */
	public function actionVeranstaltung($veranstaltung_id = 0)
	{
		$this->layout = '//layouts/column2';

		$veranstaltung = $this->actionVeranstaltung_loadData($veranstaltung_id);
		if (is_null($veranstaltung)) {
			if (Yii::app()->params['standardVeranstaltungAutoCreate']) {
				$veranstaltung                                   = new Veranstaltung();
				$veranstaltung->id                               = $veranstaltung_id;
				$veranstaltung->name                             = "Standard-Veranstaltung";
				$veranstaltung->freischaltung_antraege           = 1;
				$veranstaltung->freischaltung_aenderungsantraege = 1;
				$veranstaltung->freischaltung_kommentare         = 1;
				$veranstaltung->policy_kommentare                = Veranstaltung::$POLICY_NUR_ADMINS;
				$veranstaltung->policy_aenderungsantraege        = Veranstaltung::$POLICY_NUR_ADMINS;
				$veranstaltung->policy_antraege                  = Veranstaltung::$POLICY_NUR_ADMINS;
				$veranstaltung->typ                              = Veranstaltung::$TYP_PROGRAMM;
				$veranstaltung->save();

				$veranstaltung = $this->actionVeranstaltung_loadData($veranstaltung_id);
			} else {
				$this->redirect($this->createUrl("site/login"));
			}
		}

		/** @var array|Antrag[] $antraege */
		$antraege        = $veranstaltung->antraege;
		$antraege_sorted = array();
		$warnung         = false;
		foreach ($antraege as $ant) {
			if (!isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]])) $antraege_sorted[Antrag::$TYPEN[$ant->typ]] = array();
			$key = $ant->revision_name;
			if (isset($antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key]) && !$warnung) {
				$warnung = true;
				Yii::app()->user->setFlash("error", "Es können nicht alle Anträge angezeigt werden, da mindestens ein Kürzel ($key) mehrfach vergeben ist.");
			}
			$antraege_sorted[Antrag::$TYPEN[$ant->typ]][$key] = $ant;
		}

		/** @var null|Person $ich */
		if (Yii::app()->user->isGuest) $ich = null;
		else {
			$ich = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}

		$neueste_aenderungsantraege = Aenderungsantrag::holeNeueste($veranstaltung_id, 5);
		$neueste_antraege           = Antrag::holeNeueste($veranstaltung_id, 5);
		$neueste_kommentare         = AntragKommentar::holeNeueste($veranstaltung_id, 3);

		$meine_antraege           = array();
		$meine_aenderungsantraege = array();

		if ($ich) {
			$oCriteria        = new CDbCriteria();
			$oCriteria->alias = "antrag_unterstuetzer";
			$oCriteria->join  = "JOIN `antrag` ON `antrag`.`id` = `antrag_unterstuetzer`.`antrag_id`";
			$oCriteria->addCondition("`antrag`.`veranstaltung` = " . IntVal($veranstaltung_id));
			$oCriteria->addCondition("`antrag_unterstuetzer`.`unterstuetzer_id` = " . IntVal($ich->id));
			$oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->order = '`datum_einreichung` DESC';
			$dataProvider     = new CActiveDataProvider('AntragUnterstuetzer', array(
				'criteria' => $oCriteria,
			));
			$meine_antraege   = $dataProvider->data;

			$oCriteria        = new CDbCriteria();
			$oCriteria->alias = "aenderungsantrag_unterstuetzer";
			$oCriteria->join  = "JOIN `aenderungsantrag` ON `aenderungsantrag`.`id` = `aenderungsantrag_unterstuetzer`.`aenderungsantrag_id`";
			$oCriteria->join .= " JOIN `antrag` ON `aenderungsantrag`.`antrag_id` = `antrag`.`id`";
			$oCriteria->addCondition("`antrag`.`veranstaltung` = " . IntVal($veranstaltung_id));
			$oCriteria->addCondition("`aenderungsantrag_unterstuetzer`.`unterstuetzer_id` = " . IntVal($ich->id));
			$oCriteria->addCondition("`antrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->addCondition("`aenderungsantrag`.`status` != " . IAntrag::$STATUS_GELOESCHT);
			$oCriteria->order         = '`aenderungsantrag`.`datum_einreichung` DESC';
			$dataProvider             = new CActiveDataProvider('AenderungsantragUnterstuetzer', array(
				'criteria' => $oCriteria,
			));
			$meine_aenderungsantraege = $dataProvider->data;
		}

		$einleitungstext = $veranstaltung->getStandardtext("startseite");

		$this->render('veranstaltung_index', array(
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
	 *
	 */
	public function actionMeineAntraege()
	{
		$this->render('meineAntraege');
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
	 *
	 */
	public function actionLogin()
	{
		$this->setStdVeranstaltung();

		$model = new OAuthLoginForm();
		if (isset($_REQUEST["OAuthLoginForm"])) $model->attributes = $_REQUEST["OAuthLoginForm"];

		/** @var LightOpenID $loid */
		$loid = Yii::app()->loid->load();
		if (isset($_REQUEST["openid_mode"])) {
			if ($_REQUEST['openid_mode'] == 'cancel') {
				$err = Yii::t('core', 'Authorization cancelled');
			} else {
				try {
					$us = new AntragUserIdentity($loid);
					if ($us->authenticate()) {
						Yii::app()->user->login($us);
						$user = Person::model()->findByAttributes(array("auth" => $us->getId()));
						if (!$user) {
							$user                 = new Person;
							$user->auth           = $us->getId();
							$user->name           = $us->getName();
							$user->email          = $us->getEmail();
							$user->angelegt_datum = date("Y-m-d H:i:s");
							$user->status         = Person::$STATUS_CONFIRMED;
							$user->typ            = Person::$TYP_PERSON;
							if (Person::model()->count() == 0) {
								$user->admin = 1;
								Yii::app()->user->setState("role", "admin");
							} else {
								$user->admin = 0;
							}
							$user->save();
						} else {
							if ($user->admin) {
								//$openid->setState("role", "admin");
								Yii::app()->user->setState("role", "admin");
							}
						}
						Yii::app()->user->setState("person_id", $user->id);
						Yii::app()->user->setFlash('success', 'Willkommen!');
						$this->redirect(Yii::app()->homeUrl);
					} else {
						Yii::app()->user->setFlash("error", "Leider ist beim Einloggen ein Fehler aufgetreten.");
						$this->render('login', array("model" => $model));
						return;
					}
				} catch (Exception $e) {
					$err = Yii::t('core', $e->getMessage());
					Yii::app()->user->setFlash("error", "Leider ist beim Einloggen ein Fehler aufgetreten:<br>" . $e->getMessage());
					$this->render('login', array("model" => $model));
					return;
				}
			}

			if (!empty($err)) Yii::app()->user->setFlash("error", $err);
		} elseif (isset($_REQUEST["OAuthLoginForm"])) {
			if ($model->wurzelwerk != "") $loid->identity = "https://" . $model->wurzelwerk . ".netzbegruener.in/";
			else $loid->identity = $model->openid_identifier;

			$loid->required  = array('namePerson/friendly', 'contact/email'); //Try to get info from openid provider
			$loid->realm     = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$loid->returnUrl = $loid->realm . $_SERVER['REQUEST_URI']; //getting return URL
			if (empty($err)) {
				try {
					$url = $loid->authUrl();
					$this->redirect($url);
				} catch (Exception $e) {
					$err = Yii::t('core', $e->getMessage());
				}
			}
			if (!empty($err)) Yii::app()->user->setFlash("error", $err);
		}

		$this->render('login', array("model" => $model));
	}


	/**
	 *
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		Yii::app()->user->setFlash("success", "Bis bald!");
		$this->redirect(Yii::app()->homeUrl);
	}
}
