<?php

namespace app\models\forms;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\components\UrlHelper;
use app\models\db\{EMailLog, Site, User};
use app\models\exceptions\{Internal, Login, LoginInvalidPassword, LoginInvalidUser, MailNotSent};
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

    /** @var ExternalPasswordAuthenticatorInterface|null */
    private $externalAuthenticator;

    public function __construct(?ExternalPasswordAuthenticatorInterface $externalAuthenticator)
    {
        parent::__construct();
        $this->externalAuthenticator = $externalAuthenticator;
    }

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
    private function sendConfirmationEmail(User $user): void
    {
        if ($this->externalAuthenticator && !$this->externalAuthenticator->supportsCreatingAccounts()) {
            throw new Internal('Creating account is not supported');
        }
        $bestCode = $user->createEmailConfirmationCode();
        $params   = ['/user/confirmregistration', 'email' => $this->username, 'code' => $bestCode, 'subdomain' => null];
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
    private function doCreateAccountValidate(?Site $site): void
    {
        if ($this->externalAuthenticator && !$this->externalAuthenticator->supportsCreatingAccounts()) {
            throw new Internal('Creating account is not supported');
        }
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
        if ($this->password !== $this->passwordConfirm) {
            $this->error = \Yii::t('user', 'create_err_pwdmismatch');
            throw new Login($this->error);
        }
        if (!$this->name) {
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
    public function doCreateAccount(?Site $site): User
    {
        if ($this->externalAuthenticator && !$this->externalAuthenticator->supportsCreatingAccounts()) {
            throw new Internal('Creating account is not supported');
        }
        if ($this->externalAuthenticator && $this->externalAuthenticator->supportsCreatingAccounts()) {
            return $this->externalAuthenticator->performRegistration($this->username, $this->password);
        }

        $this->doCreateAccountValidate($site);

        $user                  = new User();
        $user->auth            = 'email:' . $this->username;
        $user->name            = $this->name;
        $user->email           = $this->username;
        $user->pwdEnc          = password_hash($this->password, PASSWORD_DEFAULT);
        $user->organizationIds = '';

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
    private function getCandidatesStdLogin(): array
    {
        $tableName = User::tableName();
        $sqlWhere1 = "`auth` = 'email:" . addslashes($this->username) . "'";

        /** @noinspection SqlResolve */
        return User::findBySql("SELECT * FROM `" . $tableName . "` WHERE $sqlWhere1")->all();
    }

    /**
     * @param Site|null $site
     * @return User[]
     */
    private function getCandidates(?Site $site): array
    {
        if ($site) {
            $methods = $site->getSettings()->loginMethods;
        } else {
            $methods = SiteSettings::$SITE_MANAGER_LOGIN_METHODS;
        }

        $candidates = [];
        if (in_array(SiteSettings::LOGIN_STD, $methods)) {
            $candidates = array_merge($candidates, $this->getCandidatesStdLogin());
        }
        return $candidates;
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws LoginInvalidUser
     * @throws LoginInvalidPassword
     * @throws Login
     */
    public function checkLogin(?Site $site): User
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

        if ($this->externalAuthenticator) {
            return $this->externalAuthenticator->performLogin($this->username, $this->password);
        }

        $candidates = $this->getCandidates($site);

        if (count($candidates) === 0) {
            $this->error = \Yii::t('user', 'login_err_username');
            throw new LoginInvalidUser($this->error);
        }
        foreach ($candidates as $tryUser) {
            if ($tryUser->validatePassword($this->password)) {
                return $tryUser;
            }
        }
        $this->error = \Yii::t('user', 'login_err_password');
        throw new LoginInvalidPassword($this->error);
    }

    /**
     * @param Site|null $site
     * @return User
     * @throws Login
     */
    public function getOrCreateUser(?Site $site): User
    {
        if ($this->createAccount) {
            return $this->doCreateAccount($site);
        } else {
            return $this->checkLogin($site);
        }
    }
}
