<?php

namespace app\models\forms;

use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\exceptions\MailNotSent;
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
            [['username', 'password'], 'required'],
            ['contact', 'required', 'message' => \Yii::t('user', 'err_contact_required')],
            [['createAccount', 'hasComments', 'openNow'], 'boolean'],
            [['username', 'password', 'passwordConfirm', 'name', 'createAccount'], 'safe'],
        ];
    }

    /**
     * @param User $user
     * @throws MailNotSent
     */
    private function sendConfirmationEmail(User $user)
    {
        $bestCode = $user->createEmailConfirmationCode();
        $params   = ['user/confirmregistration', 'email' => $this->username, 'code' => $bestCode, 'subdomain' => null];
        $link     = UrlHelper::absolutizeLink(UrlHelper::createUrl($params));

        \app\components\mail\Tools::sendWithLog(
            EMailLog::TYPE_REGISTRATION,
            null,
            $this->username,
            $user->id,
            \Yii::t('user', 'create_emailconfirm_title'),
            \Yii::t('user', 'create_emailconfirm_msg'),
            '',
            [
                '%CODE%'      => $bestCode,
                '%BEST_LINK%' => $link,
            ]
        );
    }


    /**
     * @param Site|null $site
     * @throws Login
     */
    private function doCreateAccountValidate($site)
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        if (!in_array(SiteSettings::LOGIN_STD, $methods)) {
            $this->error = \Yii::t('user', 'create_err_siteaccess');
            throw new Login($this->error);
        }
        if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]+$/siu", $this->username)) {
            $this->error = \Yii::t('user', 'create_err_emailinvalid');
            throw new Login($this->error);
        }
        if (strlen($this->password) < static::PASSWORD_MIN_LEN) {
            $this->error = str_replace('%MINLEN%', static::PASSWORD_MIN_LEN, \Yii::t('user', 'create_err_pwdlength'));
            throw new Login($this->error);
        }
        if ($this->password != $this->passwordConfirm) {
            $this->error = \Yii::t('user', 'create_err_pwdmismatch');
            throw new Login($this->error);
        }
        if ($this->name == '') {
            $this->error = \Yii::t('user', 'create_err_noname');
            throw new Login($this->error);
        }

        $auth     = 'email:' . $this->username;
        $existing = User::findOne(['auth' => $auth]);
        if ($existing) {
            /** @var User $existing */
            $this->error = \Yii::t('user', 'create_err_emailexists') . ' (' . $this->username . ')';
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

        $user         = new User();
        $user->auth   = 'email:' . $this->username;
        $user->name   = $this->name;
        $user->email  = $this->username;
        $user->pwdEnc = password_hash($this->password, PASSWORD_DEFAULT);

        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->confirmEmailAddresses) {
            $user->status         = User::STATUS_UNCONFIRMED;
            $user->emailConfirmed = 0;
        } else {
            $user->status         = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
        }

        if ($user->save()) {
            if ($params->confirmEmailAddresses) {
                $user->refresh();
                try {
                    $this->sendConfirmationEmail($user);
                    return $user;
                } catch (MailNotSent $e) {
                    $this->error = $e->getMessage();
                    throw new Login($this->error);
                }
            } else {
                return $user;
            }
        } else {
            $this->error = \Yii::t('base', 'err_unknown');
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
        $sql    = "SELECT * FROM `user` WHERE `auth` = '" . addslashes($auth) . "'";
        $sql    .= " OR (auth LIKE '$wwlike' AND email = '" . addslashes($this->username) . "')";
        return User::findBySql($sql)->all();
    }

    /**
     * @return User[]
     */
    private function getCandidatesStdLogin()
    {
        $sql_where1 = "`auth` = 'email:" . addslashes($this->username) . "'";
        return User::findBySql("SELECT * FROM `user` WHERE $sql_where1")->all();
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

        if (!in_array(SiteSettings::LOGIN_STD, $methods)) {
            $this->error = \Yii::t('user', 'login_err_siteaccess');
            throw new Login($this->error);
        }
        $candidates = $this->getCandidates($site);

        if (count($candidates) == 0) {
            $this->error = \Yii::t('user', 'login_err_username');
            throw new Login($this->error);
        }
        foreach ($candidates as $tryUser) {
            if ($tryUser->validatePassword($this->password)) {
                return $tryUser;
            }
        }
        $this->error = \Yii::t('user', 'login_err_password');
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
