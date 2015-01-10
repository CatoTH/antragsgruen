<?php

class AntragsgruenController extends CController
{
	public $layout = '//layouts/column1';
	public $menu = array();
	public $breadcrumbs = array();
	public $multimenu = null;
	public $menus_html_presidebar = null;
	public $menus_html = null;
	public $breadcrumbs_topname = null;
	public $text_comments = true;
	public $shrink_cols = false;

	/** @var null|Veranstaltung */
	public $veranstaltung = null;

	/** @var null|Veranstaltungsreihe */
	public $veranstaltungsreihe = null;


	private $_assetsBase;
	protected $robots_noindex = false;

	/**
	 *
	 */
	public function testeWartungsmodus()
	{
		if ($this->veranstaltung == null) return;
		/** @var VeranstaltungsEinstellungen $einstellungen */
		$einstellungen = $this->veranstaltung->getEinstellungen();
		if ($einstellungen->wartungs_modus_aktiv && !$this->veranstaltung->isAdminCurUser()) {
			$this->redirect($this->createUrl("veranstaltung/wartungsmodus"));
		}

		if (veranstaltungsspezifisch_erzwinge_login($this->veranstaltung) && Yii::app()->user->isGuest) {
			$this->redirect($this->createUrl("veranstaltung/login"));
		}
	}

	/**
	 *
	 */
	protected function setStdVeranstaltung()
	{
		$veranstaltung_id    = (isset($_REQUEST["id"]) ? IntVal($_REQUEST["id"]) : Yii::app()->params['standardVeranstaltung']);
		$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
	}

	/**
	 * @param string $route
	 * @param array $params
	 * @param string $ampersand
	 * @return string
	 */
	public function createUrl($route, $params = array(), $ampersand = '&')
	{
		$p = explode("/", $route);
		if ($p[0] != "infos") {
			if (!isset($params["veranstaltung_id"]) && $this->veranstaltung !== null) $params["veranstaltung_id"] = $this->veranstaltung->url_verzeichnis;
			if (MULTISITE_MODE && !isset($params["veranstaltungsreihe_id"]) && $this->veranstaltungsreihe != null) $params["veranstaltungsreihe_id"] = $this->veranstaltungsreihe->subdomain;
			if ($route == "veranstaltung/index" && !is_null($this->veranstaltungsreihe) && strtolower($params["veranstaltung_id"]) == strtolower($this->veranstaltungsreihe->aktuelle_veranstaltung->url_verzeichnis)) unset($params["veranstaltung_id"]);
			if (in_array($route, array(
				"veranstaltung/ajaxEmailIstRegistriert", "veranstaltung/anmeldungBestaetigen", "veranstaltung/benachrichtigungen", "veranstaltung/impressum", "veranstaltung/login", "veranstaltung/logout", "/admin/index/reiheAdmins", "/admin/index/reiheVeranstaltungen"
			))
			) unset($params["veranstaltung_id"]);
		}
		return parent::createUrl($route, $params, $ampersand);
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param null|Antrag $check_antrag
	 * @param null|Aenderungsantrag $check_aenderungsantrag
	 * @return null|Veranstaltung
	 */
	public function loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id = "", $check_antrag = null, $check_aenderungsantrag = null)
	{

		if ($veranstaltungsreihe_id == "") $veranstaltungsreihe_id = Yii::app()->params['standardVeranstaltungsreihe'];

		if ($veranstaltung_id == "") {
			/** @var Veranstaltungsreihe $reihe */
			$reihe = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $veranstaltungsreihe_id));
			if ($reihe) {
				$veranstaltung_id = $reihe->aktuelle_veranstaltung->url_verzeichnis;
			} else {
				$this->robots_noindex = true;
				$this->render('error', array(
					"code"    => 404,
					"html"    => true,
					"message" => "Die angegebene Veranstaltung wurde nicht gefunden. Höchstwahrscheinlich liegt da an einem Tippfehler in der Adresse im Browser.<br>
					<br>
					Auf der <a href='http://www.antragsgruen.de/'>Antragsgrün-Startseite</a> siehst du rechts eine Liste der aktiven Veranstaltungen."
				));
				Yii::app()->end();
			}
		}

		if (is_null($this->veranstaltungsreihe)) {
			if (is_numeric($veranstaltungsreihe_id)) {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByPk($veranstaltungsreihe_id);
			} else {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $veranstaltungsreihe_id));
			}
		}

		if (is_null($this->veranstaltung)) {
			$this->veranstaltung = Veranstaltung::model()->findByAttributes(array("url_verzeichnis" => $veranstaltung_id));
		}
		if (is_null($this->veranstaltung)) {
			$this->robots_noindex = true;
			$this->render("../veranstaltung/error", array(
				"code"    => 500,
				"message" => "Leider existiert die aufgerufene Seite nicht. Falls du der Meinung bist, dass das ein Fehler ist, melde dich bitte per E-Mail (info@antragsgruen.de) bei uns.",
			));
			Yii::app()->end(500);
		}

		if (strtolower($this->veranstaltung->veranstaltungsreihe->subdomain) != strtolower($veranstaltungsreihe_id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - die Veranstaltung gehört nicht zur Veranstaltungsreihe.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (is_object($check_antrag) && strtolower($check_antrag->veranstaltung->url_verzeichnis) != strtolower($veranstaltung_id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Antrag gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if ($check_aenderungsantrag != null && ($check_antrag == null || $check_aenderungsantrag->antrag_id != $check_antrag->id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Änderungsantrag gehört nicht zum Antrag.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (!is_a($this->veranstaltung, "Veranstaltung") || $this->veranstaltung->policy_kommentare == Veranstaltung::$POLICY_NIEMAND) $this->text_comments = false;

		return $this->veranstaltung;
	}


	public function getAssetsBase()
	{
		if ($this->_assetsBase === null) {
			$this->_assetsBase = Yii::app()->assetManager->publish(
				Yii::getPathOfAlias('application.assets'),
				false,
				-1,
				defined('YII_DEBUG') && YII_DEBUG
			);
		}
		return $this->_assetsBase;
	}


	/**
	 * @param string $username
	 * @return Person[]
	 */
	private function performLogin_username_password_std($username)
	{
		/** @var Person[] $users */
		if (strpos($username, "@")) {
			$sql_where1 = "auth = 'email:" . addslashes($username) . "'";
			if ($this->veranstaltungsreihe) {
				$sql_where2 = "(auth = 'ns_admin:" . IntVal($this->veranstaltungsreihe->id) . ":" . addslashes($username) . "' AND veranstaltungsreihe_namespace = " . IntVal($this->veranstaltungsreihe->id) . ")";
				$sql_where3 = "(email = '" . addslashes($username). "' AND auth LIKE 'openid:https://service.gruene.de/%')";
				$users      = Person::model()->findAllBySql("SELECT * FROM person WHERE $sql_where1 OR $sql_where2 OR $sql_where3");
			} else {
				$users = Person::model()->findAllBySql("SELECT * FROM person WHERE $sql_where1");
			}

		} else {
			$auth  = "openid:https://service.gruene.de/openid/" . $username;
			$users = Person::model()->findAllBySql("SELECT * FROM person WHERE auth = '" . addslashes($auth) . "' OR (auth LIKE 'openid:https://service.gruene.de%' AND email = '" . addslashes($username) . "')");
		}
		return $users;
	}

	/**
	 * @param string $username
	 * @return Person[]
	 */
	private function performLogin_username_password_only_namespaced_users($username)
	{
		/** @var Person[] $users */
		if (strpos($username, "@")) {
			$sql_where2 = "(auth = 'ns_admin:" . IntVal($this->veranstaltungsreihe->id) . ":" . addslashes($username) . "' AND veranstaltungsreihe_namespace = " . IntVal($this->veranstaltungsreihe->id) . ")";
			$users      = Person::model()->findAllBySql("SELECT * FROM person WHERE $sql_where2");
		} else {
			// @TODO Login über Wurzelwerk-Authentifizierten Account per BenutzerInnenname+Passwort beim Admin der Reihe ermöglichen
			return array();
		}
		return $users;
	}

	/**
	 * @param string $success_redirect
	 * @param string $username
	 * @param string $password
	 * @throws Exception
	 */
	private function performLogin_username_password($success_redirect, $username, $password)
	{
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) {
			throw new Exception("Das Login mit BenutzerInnenname und Passwort ist bei dieser Veranstaltung nicht möglich.");
		}
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			$users = $this->performLogin_username_password_only_namespaced_users($username);
		} else {
			$users = $this->performLogin_username_password_std($username);
		}

		if (count($users) == 0) {
			throw new Exception("BenutzerInnenname nicht gefunden.");
		}
		$correct_user = null;
		foreach ($users as $try_user) {
			if ((defined("IGNORE_PASSWORD_MODE") && IGNORE_PASSWORD_MODE === true) || $try_user->validate_password($password)) {
				$correct_user = $try_user;
			}
		}
		if ($correct_user) {
			$x = explode(":", $correct_user->auth);
			switch ($x[0]) {
				case "email":
					$identity = new AntragUserIdentityPasswd($x[1], $correct_user->auth);
					break;
				case "ns_admin":
					$identity = new AntragUserIdentityPasswd($x[2], $correct_user->auth);
					break;
				case "openid":
					if ($correct_user->istWurzelwerklerIn()) $identity = new AntragUserIdentityPasswd($correct_user->getWurzelwerkName(), $correct_user->auth);
					else throw new Exception("Keine Passwort-Authentifizierung mit anderen OAuth-Implementierungen möglich.");
					break;
				default:
					throw new Exception("Ungültige Authentifizierungsmethode. Wenn dieser Fehler auftritt, besteht ein Programmierfehler.");
			}
			Yii::app()->user->login($identity);

			Yii::app()->user->setState("person_id", $correct_user->id);
			Yii::app()->user->setFlash('success', 'Willkommen!');
			if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;

			$this->redirect($success_redirect);
		} else {
			throw new Exception("Falsches Passwort.");
		}

		//Yii::app()->user->login($us);
		die();
	}

	private function performCreateUser_username_password($success_redirect, $username, $password, $password_confirm, $name)
	{
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			throw new Exception("Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.");
		}
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) {
			throw new Exception("Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.");
		}
		if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]+$/siu", $username)) {
			throw new Exception("Bitte gib eine gültige E-Mail-Adresse als BenutzerInnenname ein.");
		}
		if (strlen($password) < 6) {
			throw new Exception("Das Passwort muss mindestens sechs Buchstaben lang sein.");
		}
		if ($password != $password_confirm) {
			throw new Exception("Die beiden angegebenen Passwörter stimmen nicht überein.");
		}
		if ($name == "") {
			throw new Exception("Bitte gib deinen Namen ein.");
		}
		$auth = "email:" . $username;
		$p    = Person::model()->findAllByAttributes(array("auth" => $auth));
		if (count($p) > 0) {
			throw new Exception("Es existiert bereits ein Zugang mit dieser E-Mail-Adresse.");
		}

		$person                   = new Person;
		$person->auth             = "email:" . $username;
		$person->name             = $name;
		$person->email            = $username;
		$person->email_bestaetigt = 0;
		$person->angelegt_datum   = date("Y-m-d H:i:s");
		$person->status           = Person::$STATUS_UNCONFIRMED;
		$person->typ              = Person::$TYP_PERSON;
		$person->pwd_enc          = Person::create_hash($password);

		if ($person->save()) {
			$person->refresh();
			$best_code = $person->createEmailBestaetigungsCode();
			$link      = Yii::app()->getBaseUrl(true) . $this->createUrl("veranstaltung/anmeldungBestaetigen", array("email" => $username, "code" => $best_code, "veranstaltung_id" => null));
			$send_text = "Hallo,\n\num deinen Antragsgrün-Zugang zu aktivieren, klicke entweder auf folgenden Link:\n%best_link%\n\n"
				. "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
				. "Liebe Grüße,\n\tDas Antragsgrün-Team.";
			AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_REGISTRIERUNG, $username, $person->id, "Anmeldung bei Antragsgrün", $send_text, null, array(
				"%code%"      => $best_code,
				"%best_link%" => $link,
			));
			$this->redirect($this->createUrl("veranstaltung/anmeldungBestaetigen", array("email" => $username, "veranstaltung_id" => null)));
		} else {
			$msg_err = "Leider ist ein (ungewöhnlicher) Fehler aufgetreten.";
			$errs    = $person->getErrors();
			foreach ($errs as $err) foreach ($err as $e) $msg_err .= $e;
			throw new Exception($msg_err);
		}
	}

	/**
	 * @param OAuthLoginForm $model
	 * @param array $form_params
	 * @throws Exception
	 */
	protected function performLogin_OAuth_init(&$model, $form_params)
	{
		$model->attributes = $form_params;

		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk && $model->wurzelwerk == "") {
			throw new Exception("Bei dieser Veranstaltung ist kein Login per OpenID möglich.");
		}

		if (stripos($model->openid_identifier, "yahoo") !== false) {
			throw new Exception("Leider ist wegen technischen Problemen ein Login mit Yahoo momentan nicht möglich.");
		} else {
			/** @var LightOpenID $loid */
			$loid = Yii::app()->loid->load();
			//if ($model->wurzelwerk != "") $loid->identity = "https://" . $model->wurzelwerk . ".netzbegruener.in/";
			if ($model->wurzelwerk != "") $loid->identity = "https://service.gruene.de/openid/" . $model->wurzelwerk;
			else $loid->identity = $model->openid_identifier;

			$loid->required  = array('namePerson/friendly', 'contact/email'); //Try to get info from openid provider
			$loid->realm     = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$loid->returnUrl = $loid->realm . yii::app()->getRequest()->requestUri;
			if (empty($err)) {
				try {
					$url = $loid->authUrl();
					$this->redirect($url);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
		if (!empty($err)) Yii::app()->user->setFlash("error", $err);
	}


	/**
	 * @param AntragUserIdentityOAuth $user_identity
	 */
	protected function performLogin_OAuth_create_user($user_identity)
	{
		$email = $user_identity->getEmail();

		$user                   = new Person;
		$user->auth             = $user_identity->getId();
		$user->name             = $user_identity->getName();
		$user->email            = $email;
		$user->email_bestaetigt = 0;
		$user->angelegt_datum   = date("Y-m-d H:i:s");
		$user->status           = Person::$STATUS_CONFIRMED;
		$user->typ              = Person::$TYP_PERSON;

		$password      = substr(md5(uniqid()), 0, 8);
		$user->pwd_enc = Person::create_hash($password);

		$user->save();

		if (trim($email) != "") {
			$user->refresh();
			$send_text = "Hallo!\n\nDein Zugang bei Antragsgrün wurde eben eingerichtet.\n\n" .
				"Du kannst dich mit folgenden Daten einloggen:\nBenutzerInnenname: $email\nPasswort: %passwort%\n\n" .
				"Das Passwort kannst du hier ändern:\n" .
				yii::app()->getBaseUrl(true) . yii::app()->createUrl("infos/passwort") . "\n\n" .
				"Außerdem ist auch weiterhin ein Login über deinen Wurzelwerk-Zugang möglich.\n\n" .
				"Liebe Grüße,\n  Das Antragsgrün-Team";
			AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_REGISTRIERUNG, $email, $user->id, "Dein Antragsgrün-Zugang", $send_text, null, array(
				"%passwort%" => $password,
			));
		}

	}


	/**
	 * @param string $success_redirect
	 * @param string $openid_mode
	 * @throws Exception
	 */
	private function performLogin_OAuth_callback($success_redirect, $openid_mode)
	{
		/** @var LightOpenID $loid */
		$loid = Yii::app()->loid->load();
		if ($openid_mode != 'cancel') {
			try {
				$us = new AntragUserIdentityOAuth($loid);
				if ($us->authenticate()) {
					if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) {
						if (strpos($us->getId(), "openid:https://service.gruene.de/openid/") !== 0) throw new Exception("Bei dieser Veranstaltung ist nur ein Login über das Wurzelwerk zulässig.");
					}
					/** @var Person $user */
					$user = Person::model()->findByAttributes(array("auth" => $us->getId()));
					if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
						$ist_admin = false;
						foreach ($this->veranstaltungsreihe->admins as $admin) if ($user->id == $admin->id) $ist_admin = true;
						if (Yii::app()->params['admin_user_id'] > 0 && Yii::app()->params['admin_user_id'] == $user->id) $ist_admin = true;

						if (!$ist_admin) throw new Exception("Das Einloggen über OpenID ist bei dieser Veranstaltung nur für Admins möglich.");
					}
					if (!$user) {
						$this->performLogin_OAuth_create_user($us);
						$user = Person::model()->findByAttributes(array("auth" => $us->getId()));
						if (!$user) {
							throw new Exception("Leider ist beim Einloggen ein interner Fehler aufgetreten.");
						}
					}
					Yii::app()->user->login($us);
					Yii::app()->user->setState("person_id", $user->id);
					Yii::app()->user->setFlash('success', 'Willkommen!');
					if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;
					$this->redirect($success_redirect);
				} else {
					throw new Exception("Leider ist beim Einloggen ein Fehler aufgetreten.");
				}
			} catch (Exception $e) {
				throw new Exception("Leider ist beim Einloggen ein Fehler aufgetreten:<br>" . $e->getMessage());
			}
		}

		if (!empty($err)) Yii::app()->user->setFlash("error", $err);
	}

	/**
	 * @param string $success_redirect
	 * @param string $login
	 * @throws Exception
	 */
	protected function performLogin_from_email_params($success_redirect, $login)
	{
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_namespaced_accounts) {
			throw new Exception("Diese Form des Logins ist bei dieser Veranstaltung nicht möglich.");
		}
		if ($this->veranstaltungsreihe && $this->veranstaltungsreihe->getEinstellungen()->antrag_neu_nur_wurzelwerk) {
			throw new Exception("Diese Form des Logins ist bei dieser Veranstaltung nicht möglich.");
		}

		/** @var Person $user */
		$user = Person::model()->findByAttributes(array("id" => $login));
		if ($user === null) {
			throw new Exception("BenutzerInnenname nicht gefunden");
		}
		$identity = new AntragUserIdentityPasswd($user->getWurzelwerkName(), $user->auth);
		Yii::app()->user->login($identity);

		Yii::app()->user->setState("person_id", $user->id);
		Yii::app()->user->setFlash('success', 'Willkommen!');
		if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;

		$this->redirect($success_redirect);
	}


	/**
	 * @param string $success_redirect
	 * @throws Exception
	 * @return OAuthLoginForm
	 */
	protected function performLogin($success_redirect)
	{

		$model = new OAuthLoginForm();

		if (isset($_REQUEST["password"]) && $_REQUEST["password"] != "" && isset($_REQUEST["username"])) {
			if (isset($_REQUEST["neuer_account"])) {
				$this->performCreateUser_username_password($success_redirect, $_REQUEST["username"], $_REQUEST["password"], $_REQUEST["password_confirm"], $_REQUEST["name"]);
			} else {
				$this->performLogin_username_password($success_redirect, $_REQUEST["username"], $_REQUEST["password"]);
			}
		} elseif (isset($_REQUEST["openid_mode"])) {
			$this->performLogin_OAuth_callback($success_redirect, $_REQUEST['openid_mode']);
		} elseif (isset($_REQUEST["OAuthLoginForm"])) {
			$this->performLogin_OAuth_init($model, $_REQUEST["OAuthLoginForm"]);
		} elseif (isset($_REQUEST["login"]) && $_REQUEST["login_sec"] == AntiXSS::createToken($_REQUEST["login"])) {
			$this->performLogin_from_email_params($success_redirect, $_REQUEST["login"]);
		}
		return $model;
	}

	/**
	 * @param string $email
	 * @param string|null $password
	 * @param string|null $bestaetigungscode
	 * @return array
	 */
	protected function loginOderRegistrieren_backend($email, $password = null, $bestaetigungscode = null)
	{
		$msg_ok         = $msg_err = "";
		$correct_person = null;

		$person = Person::model()->findAll(array(
			"condition" => "email='" . addslashes($email) . "' AND pwd_enc != ''"
		));
		if (count($person) > 0) {
			/** @var Person $p */
			$p = $person[0];
			if ($p->email_bestaetigt) {
				if ($p->validate_password($password)) {
					$correct_person = $p;

					if ($p->istWurzelwerklerIn()) $identity = new AntragUserIdentityPasswd($p->getWurzelwerkName(), $p->auth);
					else $identity = new AntragUserIdentityPasswd($p->email, $p->auth);
					Yii::app()->user->login($identity);
				} else {
					$msg_err = "Das angegebene Passwort ist leider falsch.";
				}
			} else {
				if ($p->checkEmailBestaetigungsCode($bestaetigungscode)) {
					$p->email_bestaetigt = 1;
					if ($p->save()) {
						$msg_ok   = "Die E-Mail-Adresse wurde freigeschaltet. Ab jetzt wirst du entsprechend deinen Einstellungen benachrichtigt.";
						$identity = new AntragUserIdentityPasswd($p->email, $p->auth);
						Yii::app()->user->login($identity);
					} else {
						$msg_err = "Ein sehr seltsamer Fehler ist aufgetreten.";
					}
				} else {
					$msg_err = "Leider stimmt der angegebene Code nicht";
				}
			}
		} else {
			$email                    = trim($email);
			$passwort                 = Person::createPassword();
			$person                   = new Person;
			$person->auth             = "email:" . $email;
			$person->name             = $email;
			$person->email            = $email;
			$person->email_bestaetigt = 0;
			$person->angelegt_datum   = date("Y-m-d H:i:s");
			$person->status           = Person::$STATUS_UNCONFIRMED;
			$person->typ              = Person::$TYP_PERSON;
			$person->pwd_enc          = Person::create_hash($passwort);
			$person->admin            = 0;

			if ($person->save()) {
				$person->refresh();
				$best_code = $person->createEmailBestaetigungsCode();
				$link      = Yii::app()->getBaseUrl(true) . $this->createUrl("veranstaltung/benachrichtigungen", array("code" => $best_code));
				$send_text = "Hallo,\n\num Benachrichtigungen bei Antragsgrün zu erhalten, klicke entweder auf folgenden Link:\n%best_link%\n\n"
					. "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
					. "Das Passwort für den Antragsgrün-Zugang lautet: %passwort%\n\n"
					. "Liebe Grüße,\n\tDas Antragsgrün-Team.";
				AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_REGISTRIERUNG, $email, $person->id, "Anmeldung bei Antragsgrün", $send_text, null, array(
					"%code%"      => $best_code,
					"%best_link%" => $link,
					"%passwort%"  => $passwort,
				));
				$correct_person = $person;

				$identity = new AntragUserIdentityPasswd($email, $person->auth);
				Yii::app()->user->login($identity);
			} else {
				$msg_err = "Leider ist ein (ungewöhnlicher) Fehler aufgetreten.";
				$errs    = $person->getErrors();
				foreach ($errs as $err) foreach ($err as $e) $msg_err .= $e;
			}

		}
		return array($correct_person, $msg_ok, $msg_err);
	}


	/**
	 * @static
	 * @param array $submit_data
	 * @param int $submit_status
	 * @param bool $andereAntragstellerInErlaubt
	 * @return Person
	 */
	public static function getCurrenPersonOrCreateBySubmitData($submit_data, $submit_status, $andereAntragstellerInErlaubt)
	{
		if (Yii::app()->user->isGuest) {
			$person_id = Yii::app()->user->getState("person_id");
			if ($person_id) {
				$model_person = Person::model()->findByAttributes(array("id" => $person_id));
			} else {
				$model_person                 = new Person();
				$model_person->attributes     = $submit_data;
				$model_person->admin          = 0;
				$model_person->angelegt_datum = new CDbExpression('NOW()');
				$model_person->status         = $submit_status;

				if (!$model_person->save()) {
					foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Person konnte nicht angelegt werden: $key: $val2");
					$model_person = null;
				} else {
					Yii::app()->user->setState("person_id", $model_person->id);
				}
			}
		} elseif ($andereAntragstellerInErlaubt && isset($_REQUEST["andere_antragstellerIn"])) {
			$model_person                 = new Person();
			$model_person->attributes     = $submit_data;
			$model_person->admin          = 0;
			$model_person->angelegt_datum = new CDbExpression('NOW()');
			$model_person->status         = $submit_status;

			if (!$model_person->save()) {
				foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Person konnte nicht angelegt werden: $key: $val2");
				$model_person = null;
			}
		} else {
			$model_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}
		return $model_person;
	}


}
