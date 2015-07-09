<?php

namespace app\models\forms;

use app\components\PasswordFunctions;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Site as SiteSettings;
use yii\base\Model;

class LoginUsernamePasswordForm extends Model
{
    const PASSWORD_MIN_LEN = 4;

    /** @var string */
    public $username;
    public $password;
    public $passwordConfirm;
    public $name;
    public $error;

    /** @var bool */
    public $createAccount = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['username', 'password',], 'required'],
            ['contact', 'required', 'message' => 'Du musst eine Kontaktadresse angeben.'],
            [['createAccount', 'hasComments', 'openNow'], 'boolean'],
            [['username', 'password', 'passwordConfirm', 'name', 'createAccount'], 'safe'],
        ];
    }

    /**
     * @param User $user
     */
    private function sendConfirmationEmail(User $user)
    {
        $bestCode = $user->createEmailConfirmationCode();
        $params   = ["user/confirmregistration", "email" => $this->username, "code" => $bestCode, "subdomain" => null];
        $link     = UrlHelper::absolutizeLink(UrlHelper::createUrl($params));

        $send_text = "Hallo,\n\num deinen Antragsgrün-Zugang zu aktivieren, klicke entweder auf folgenden Link:\n";
        $send_text .= "%bestLink%\n\n"
            . "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
            . "Liebe Grüße,\n\tDas Antragsgrün-Team.";

        Tools::sendMailLog(
            EmailLog::TYPE_REGISTRATION,
            $this->username,
            $user->id,
            'Anmeldung bei Antragsgrün',
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
    private function doCreateAccountValidate($site)
    {
        if (!in_array(SiteSettings::LOGIN_STD, $site->getSettings()->loginMethods)) {
            $this->error = 'Das Anlegen von Accounts ist bei dieser Veranstaltung nicht möglich.';
            throw new Login($this->error);
        }
        if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]+$/siu", $this->username)) {
            $this->error = 'Bitte gib eine gültige E-Mail-Adresse als BenutzerInnenname ein.';
            throw new Login($this->error);
        }
        if (strlen($this->password) < static::PASSWORD_MIN_LEN) {
            $this->error = 'Das Passwort muss mindestens sechs Buchstaben lang sein.';
            throw new Login($this->error);
        }
        if ($this->password != $this->passwordConfirm) {
            $this->error = 'Die beiden angegebenen Passwörter stimmen nicht überein.';
            throw new Login($this->error);
        }
        if ($this->name == '') {
            $this->error = 'Bitte gib deinen Namen ein.';
            throw new Login($this->error);
        }

        $auth     = 'email:' . $this->username;
        $existing = User::findOne(['auth' => $auth]);
        if ($existing) {
            /** @var User $existing */
            $this->error = 'Es existiert bereits ein Zugang mit dieser E-Mail-Adresse ($auth): ' .
                print_r($existing->getAttributes(), true);
            throw new Login($this->error);
        }
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function doCreateAccount($site)
    {
        $this->doCreateAccountValidate($site);

        $user                 = new User();
        $user->auth           = 'email:' . $this->username;
        $user->name           = $this->name;
        $user->email          = $this->username;
        $user->emailConfirmed = 0;
        $user->pwdEnc         = PasswordFunctions::createHash($this->password);

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->confirmEmailAddresses) {
            $user->status = User::STATUS_UNCONFIRMED;
        } else {
            $user->status = User::STATUS_CONFIRMED;
        }

        if ($user->save()) {
            $user->refresh();
            $this->sendConfirmationEmail($user);
            return $user;
        } else {
            $this->error = 'Leider ist ein (ungewöhnlicher) Fehler aufgetreten.';
            throw new Login($this->error);
        }
    }

    /**
     * @return User[]
     */
    private function getCandidatesWurzelwerk()
    {
        $wwlike = "openid:https://service.gruene.de/%";
        $auth   = "openid:https://service.gruene.de/openid/" . $this->username;
        $sql    = "SELECT * FROM user WHERE auth = '" . addslashes($auth) . "'";
        $sql .= " OR (auth LIKE '$wwlike' AND email = '" . addslashes($this->username) . "')";
        return User::findBySql($sql)->all();
    }

    /**
     * @return User[]
     */
    private function getCandidatesStdLogin()
    {
        $sql_where1 = "auth = 'email:" . addslashes($this->username) . "'";
        return User::findBySql("SELECT * FROM user WHERE $sql_where1 AND siteNamespaceId IS NULL")->all();
    }

    /**
     * @param Site $site
     * @return User[]
     */
    private function getCandidatesNamespaced(Site $site)
    {
        $auth   = addslashes("ns_admin:" . IntVal($site->id) . ":" . addslashes($this->username) . "'");
        $siteId = IntVal($site->id);
        return User::findBySql("SELECT * FROM user WHERE auth = '$auth' AND siteNamespaceId = $siteId")->all();
    }


    /**
     * @param Site|null $site
     * @return User[]
     */
    private function getCandidates($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        /** @var AntragsgruenApp $app */
        $app        = \yii::$app->params;
        $candidates = [];
        if (in_array(SiteSettings::LOGIN_STD, $methods)) {
            $candidates = array_merge($candidates, $this->getCandidatesStdLogin());
        }
        if (in_array(SiteSettings::LOGIN_NAMESPACED, $methods) && $site) {
            $candidates = array_merge($candidates, $this->getCandidatesNamespaced($site));
        }
        if (in_array(SiteSettings::LOGIN_WURZELWERK, $methods) && $app->hasWurzelwerk) {
            $candidates = array_merge($candidates, $this->getCandidatesWurzelwerk());
        }
        return $candidates;
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    private function checkLogin($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        if (!in_array(SiteSettings::LOGIN_STD, $methods) && !in_array(SiteSettings::LOGIN_NAMESPACED, $methods)) {
            $this->error = 'Das Login mit BenutzerInnenname und Passwort ist bei dieser Veranstaltung nicht möglich.';
            throw new Login($this->error);
        }
        $candidates = $this->getCandidates($site);

        if (count($candidates) == 0) {
            $this->error = 'BenutzerInnenname nicht gefunden.';
            throw new Login($this->error);
        }
        foreach ($candidates as $tryUser) {
            if ($tryUser->validatePassword($this->password)) {
                return $tryUser;
            }
        }
        $this->error = 'Falsches Passwort.';
        throw new Login($this->error);
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    public function getOrCreateUser($site)
    {
        if ($this->createAccount) {
            return $this->doCreateAccount($site);
        } else {
            return $this->checkLogin($site);
        }
    }
}
