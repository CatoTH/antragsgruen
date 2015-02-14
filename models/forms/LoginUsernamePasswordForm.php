<?php

namespace app\models\forms;

use app\components\PasswordFunctions;
use app\components\Tools;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Login;
use yii\helpers\Url;

class LoginUsernamePasswordForm extends \yii\base\Model
{
    const PASSWORD_MIN_LEN = 4;

    /** @var string */
    public $username;
    public $password;
    public $passwordConfirm;
    public $name;

    /** @var bool */
    public $createAccount = true;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [
                ['username', 'password',], 'required'
            ],
            [
                'contact', 'required', 'message' => 'Du musst eine Kontaktadresse angeben.'
            ],
            [['createAccount', 'hasComments', 'openNow'], 'boolean'],
            [['username', 'password', 'passwordConfirm', 'name', 'createAccount'], 'safe'],
        ];
    }

    private function sendConfirmationEmail(User $user)
    {
        $bestCode = $user->createEmailConfirmationCode();
        $link     = \Yii::$app->request->baseUrl . Url::toRoute([
                "user/confirmregistration",
                "email"     => $this->username,
                "code"      => $bestCode,
                "subdomain" => null
            ]);

        $send_text = "Hallo,\n\num deinen Antragsgrün-Zugang zu aktivieren, klicke entweder auf folgenden Link:\n";
        $send_text .= "%bestLink%\n\n"
            . "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
            . "Liebe Grüße,\n\tDas Antragsgrün-Team.";

        Tools::sendMailLog(
            EmailLog::TYPE_REGISTRATION,
            $this->username,
            $user->id,
            "Anmeldung bei Antragsgrün",
            $send_text,
            null,
            null,
            [
                "%code%"     => $bestCode,
                "%bestLink%" => $link,
            ]
        );
    }


    /**
     * @param Site|null $site
     * @throws Login
     */
    private function createAccountValidate($site)
    {
        if ($site && $site->getSettings()->onlyNamespacedAccounts) {
            throw new Login("Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.");
        }
        if ($site && $site->getSettings()->onlyWurzelwerk) {
            throw new Login("Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.");
        }
        if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]+$/siu", $this->username)) {
            throw new Login("Bitte gib eine gültige E-Mail-Adresse als BenutzerInnenname ein.");
        }
        if (strlen($this->password) < static::PASSWORD_MIN_LEN) {
            throw new Login("Das Passwort muss mindestens sechs Buchstaben lang sein.");
        }
        if ($this->password != $this->passwordConfirm) {
            throw new Login("Die beiden angegebenen Passwörter stimmen nicht überein.");
        }
        if ($this->name == "") {
            throw new Login("Bitte gib deinen Namen ein.");
        }

        $auth = "email:" . $this->username;
        if (User::findOne(['auth' => $auth])) {
            throw new Login("Es existiert bereits ein Zugang mit dieser E-Mail-Adresse.");
        }
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function createAccount($site)
    {
        $this->createAccountValidate($site);

        $user                 = new User();
        $user->auth           = "email:" . $this->username;
        $user->name           = $this->name;
        $user->email          = $this->name;
        $user->emailConfirmed = 0;
        $user->dateCreation   = date("Y-m-d H:i:s");
        $user->status         = User::STATUS_UNCONFIRMED;
        $user->pwdEnc         = PasswordFunctions::createHash($this->password);

        if ($user->save()) {
            $user->refresh();
            $this->sendConfirmationEmail($user);
            return $user;
        } else {
            throw new Login("Leider ist ein (ungewöhnlicher) Fehler aufgetreten.");
        }
    }

    /**
     * @param Site|null $site
     * @return User[]
     */
    private function checkLoginOnlyNamespaced($site)
    {
        /** @var User[] $users */
        if (strpos($this->username, "@")) {
            $sql_where2 = "(auth = 'ns_admin:" . IntVal($site->id) . ":" . addslashes($this->username) . "'";
            $sql_where2 .= " AND veranstaltungsreihe_namespace = " . IntVal($site->id) . ")";
            return User::findBySql("SELECT * FROM user WHERE $sql_where2")->all();
        } else {
            // @TODO Login über Wurzelwerk-Authentifizierten Account per
            // BenutzerInnenname+Passwort beim Admin der Reihe ermöglichen
            return array();
        }
    }

    /**
     * @param Site|null $site
     * @return User[]
     */
    private function checkLoginStd($site)
    {
        $wwlike = "openid:https://service.gruene.de/%";
        /** @var User[] $users */
        if (strpos($this->username, "@")) {
            $sql_where1 = "auth = 'email:" . addslashes($this->username) . "'";
            if ($site) {
                $sql_where2 = "(auth = 'ns_admin:" . IntVal($site->id) . ":" . addslashes($this->username) . "'";
                $sql_where2 .= " AND veranstaltungsreihe_namespace = " . IntVal($site->id) . ")";
                $sql_where3 = "(email = '" . addslashes($this->username) . "' AND auth LIKE '$wwlike')";
                return User::findBySql("SELECT * FROM user WHERE $sql_where1 OR $sql_where2 OR $sql_where3")->all();
            } else {
                return User::findBySql("SELECT * FROM user WHERE $sql_where1")->all();
            }

        } else {
            $auth = "openid:https://service.gruene.de/openid/" . $this->username;
            $sql  = "SELECT * FROM user WHERE auth = '" . addslashes($auth) . "'";
            $sql .= " OR (auth LIKE '$wwlike' AND email = '" . addslashes($this->username) . "')";
            return User::findBySql($sql)->all();
        }
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function checkLogin($site)
    {
        if ($site && $site->getSettings()->onlyWurzelwerk) {
            throw new Login("Das Login mit BenutzerInnenname und Passwort ist bei dieser Veranstaltung nicht möglich.");
        }
        if ($site && $site->getSettings()->onlyNamespacedAccounts) {
            $candidates = $this->checkLoginOnlyNamespaced($site);
        } else {
            $candidates = $this->checkLoginStd($site);
        }

        if (count($candidates) == 0) {
            throw new Login("BenutzerInnenname nicht gefunden.");
        }
        foreach ($candidates as $tryUser) {
            if ($tryUser->validatePassword($this->password)) {
                return $tryUser;
            }
        }
        throw new Login("Falsches Passwort.");
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    public function getOrCreateUser($site)
    {
        if ($this->createAccount) {
            return $this->createAccount($site);
        } else {
            return $this->checkLogin($site);
        }
    }
}
