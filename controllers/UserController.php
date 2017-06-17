<?php

namespace app\controllers;

use app\components\Tools;
use app\components\UrlHelper;
use app\components\WurzelwerkAuthClient;
use app\components\WurzelwerkAuthClientTest;
use app\components\WurzelwerkSamlClient;
use app\models\db\EMailBlacklist;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\exceptions\Login;
use app\models\exceptions\MailNotSent;
use app\models\forms\LoginUsernamePasswordForm;
use app\models\settings\AntragsgruenApp;
use Yii;
use yii\helpers\Html;

class UserController extends Base
{
    public $enableCsrfValidation = false;

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'auth' => [
                'class'           => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successCallback'],
            ],
        ];
    }

    /**
     * @param User $user
     */
    protected function loginUser(User $user)
    {
        Yii::$app->user->login($user, $this->getParams()->autoLoginDuration);
    }

    /**
     * @param string $backUrl
     * @return int|string
     */
    public function actionLoginsaml($backUrl = '')
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;
        if (!$params->isSamlActive()) {
            return 'SAML is not supported';
        }

        if ($backUrl == '') {
            $backUrl = \Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        try {
            $samlClient = new WurzelwerkSamlClient();
            $samlClient->requireAuth();

            $this->loginUser($samlClient->getOrCreateUser());

            $this->redirect($backUrl);
        } catch (\Exception $e) {
            return $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }

        return '';
    }

    /**
     * @param string $backUrl
     * @return int|string
     */
    public function actionLoginwurzelwerk($backUrl = '')
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;
        if (!$params->hasWurzelwerk) {
            return 'Wurzelwerk is not supported';
        }

        if ($backUrl == '') {
            $backUrl = \Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        if (YII_ENV == 'test') {
            $client = new WurzelwerkAuthClientTest();
            if ($this->getRequestValue('username')) {
                $client->setClaimedId($this->getRequestValue('username'));
                $this->redirect($client->getFakeRedirectUrl($backUrl));
                return '';
            }
        } else {
            $client = new WurzelwerkAuthClient();
        }

        if ($this->isRequestSet('openid_claimed_id')) {
            $client->setClaimedId($this->getRequestValue('openid_claimed_id'));
        } elseif ($this->isRequestSet('username')) {
            $client->setClaimedId($this->getRequestValue('username'));
        }

        if ($this->isRequestSet('openid_mode')) {
            if ($this->getRequestValue('openid_mode') == 'error') {
                \yii::$app->session->setFlash('error', 'An error occurred: ' . $this->getRequestValue('openid_error'));
                return $this->actionLogin($backUrl);
            } elseif ($this->getRequestValue('openid_mode') == 'cancel') {
                \yii::$app->session->setFlash('error', 'Aborted login');
                return $this->actionLogin($backUrl);
            } elseif ($client->validate()) {
                $this->loginUser($client->getOrCreateUser());
                $this->redirect($backUrl);
            } else {
                \yii::$app->session->setFlash('error', \Yii::t('user', 'err_unknown_ww_repeat'));
                return $this->actionLogin($backUrl);
            }
            return '';
        }

        try {
            $url = $client->buildAuthUrl();
        } catch (\Exception $e) {
            return $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
        return Yii::$app->getResponse()->redirect($url);
    }


    /**
     * @param string $backUrl
     * @return string
     */
    public function actionLogin($backUrl = '')
    {
        $this->layout = 'column2';

        if ($backUrl == '') {
            $backUrl = '/';
        }

        $usernamePasswordForm = new LoginUsernamePasswordForm();

        if ($this->isPostSet('loginusernamepassword')) {
            $usernamePasswordForm->setAttributes(\Yii::$app->request->post());
            try {
                $user = $usernamePasswordForm->getOrCreateUser($this->site);
                $this->loginUser($user);

                $unconfirmed = $user->status == User::STATUS_UNCONFIRMED;
                if ($unconfirmed && $this->getParams()->confirmEmailAddresses) {
                    $backUrl = UrlHelper::createUrl([
                        'user/confirmregistration',
                        'backUrl' => $backUrl,
                        'email'   => $user->email,
                    ]);
                } else {
                    \Yii::$app->session->setFlash('success', \Yii::t('user', 'welcome'));
                }

                /* 307 breaks user/NoEmailConfirmationCept
                $this->redirect($backUrl, 307);
                \Yii::$app->end(307);
                */
                $this->redirect($backUrl);
                \Yii::$app->end(302);
            } catch (Login $e) {
                $usernamePasswordForm->error = $e->getMessage();
            }
        }

        if (\Yii::$app->session->getFlash('error')) {
            $usernamePasswordForm->error = \Yii::$app->session->getFlash('error');
            \Yii::$app->session->removeFlash('error');
        }


        return $this->render(
            'login',
            [
                'backUrl'              => $backUrl,
                'usernamePasswordForm' => $usernamePasswordForm,
            ]
        );
    }

    /**
     * @param string $backUrl
     * @param string $email
     * @return string
     */
    public function actionConfirmregistration($backUrl = '', $email = '')
    {
        $msgError = '';

        if ($this->isRequestSet('email') && $this->isRequestSet('code')) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            if (!$user) {
                $msgError = \Yii::t('user', 'err_email_acc_notfound');
            } elseif ($user->checkEmailConfirmationCode($this->getRequestValue('code'))) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $this->loginUser($user);
                    return $this->render('registration_confirmed');
                }
            } else {
                $msgError = \Yii::t('user', 'err_code_wrong');
            }
        }

        return $this->render(
            'confirm_registration',
            [
                'email'   => $email,
                'errors'  => $msgError,
                'backUrl' => $backUrl
            ]
        );
    }

    /**
     * @param string $backUrl
     * @return int|string
     */
    private function logoutSaml($backUrl = '')
    {
        try {
            /** @var AntragsgruenApp $params */
            $params        = Yii::$app->params;
            $backSubdomain = UrlHelper::getSubdomain($backUrl);
            $currDomain    = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
            $currSubdomain = UrlHelper::getSubdomain($currDomain);

            if ($currSubdomain) {
                // First step on the subdomain: logout and redirect to the main domain
                \Yii::$app->user->logout();
                $backUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $backUrl;
                $this->redirect($params->domainPlain . 'user/logout?backUrl=' . urlencode($backUrl));
            } elseif ($backSubdomain) {
                // Second step: we are on the main domain. Logout and redirect to the subdomain
                $samlClient = new WurzelwerkSamlClient();
                $samlClient->logout();
                $this->redirect($backUrl);
            } else {
                // No subdomain is involved, local logout on the main domain
                $samlClient = new WurzelwerkSamlClient();
                $samlClient->logout();
                $this->redirect($backUrl);
            }
            return '';
        } catch (\Exception $e) {
            return $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
    }


    /**
     * @param string $backUrl
     * @return int|string
     */
    public function actionLogout($backUrl)
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;

        if ($backUrl == '') {
            $backUrl = \Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        if ($params->isSamlActive()) {
            return $this->logoutSaml($backUrl);
        } else {
            \Yii::$app->user->logout();
            $this->redirect($backUrl, 307);
            return '';
        }
    }

    /**
     * @param string $email
     * @param string $code
     * @return string
     */
    public function actionRecovery($email = '', $code = '')
    {
        if ($this->isPostSet('send')) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            if (!$user) {
                $msg = str_replace('%USER%', $this->getRequestValue('email'), \Yii::t('user', 'err_user_notfound'));
                \yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $this->getRequestValue('email');
                try {
                    $user->sendRecoveryMail();
                    $msg = \Yii::t('user', 'pwd_recovery_sent');
                    \yii::$app->session->setFlash('success', $msg);
                } catch (MailNotSent $e) {
                    $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    \yii::$app->session->setFlash('error', $errMsg);
                } catch (FormError $e) {
                    $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    \yii::$app->session->setFlash('error', $errMsg);
                }
            }
        }

        if ($this->isPostSet('recover')) {
            /** @var User $user */
            $user     = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;
            if (!$user) {
                $msg = str_replace('%USER%', $this->getRequestValue('email'), \Yii::t('user', 'err_user_notfound'));
                \yii::$app->session->setFlash('error', $msg);
            } elseif (mb_strlen($this->getRequestValue('newPassword')) < $pwMinLen) {
                $msg = str_replace('%MINLEN%', $pwMinLen, \Yii::t('user', 'err_pwd_length'));
                \yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $this->getRequestValue('email');
                try {
                    if ($user->checkRecoveryToken($this->getRequestValue('recoveryCode'))) {
                        $user->changePassword($this->getRequestValue('newPassword'));
                        return $this->render('recovery_confirmed');
                    }
                } catch (ExceptionBase $e) {
                    \yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('recovery', ['preEmail' => $email, 'preCode' => $code]);
    }

    /**
     * @param string $email
     * @param string $code
     * @return \yii\web\Response
     */
    public function actionEmailchange($email, $code)
    {
        $this->forceLogin();
        $user = User::getCurrentUser();
        try {
            $user->changeEmailAddress($email, $code);
            \yii::$app->session->setFlash('success', \Yii::t('user', 'emailchange_done'));
        } catch (FormError $e) {
            \yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(UrlHelper::createUrl('user/myaccount'));
    }

    /**
     * @return string
     */
    public function actionMyaccount()
    {
        $this->forceLogin();

        $user     = User::getCurrentUser();
        $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

        if ($this->isPostSet('resendEmailChange')) {
            $changeRequested = $user->getChangeRequestedEmailAddress();
            if ($changeRequested) {
                $lastRequest = time() - Tools::dateSql2timestamp($user->emailChangeAt);
                if ($lastRequest < 5 * 60) {
                    \yii::$app->session->setFlash('error', \Yii::t('user', 'err_emailchange_flood'));
                } else {
                    try {
                        $user->sendEmailChangeMail($changeRequested);
                        \yii::$app->session->setFlash('success', \Yii::t('user', 'emailchange_sent'));
                    } catch (MailNotSent $e) {
                        $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                        \yii::$app->session->setFlash('error', $errMsg);
                    }
                }
            }
        }
        if ($this->isPostSet('save')) {
            $post = \Yii::$app->request->post();
            if (trim($post['name']) != '') {
                if ($user->name != $post['name']) {
                }
                $user->name = $post['name'];
            }

            if ($post['pwd'] != '' || $post['pwd2'] != '') {
                if ($post['pwd'] != $post['pwd2']) {
                    \yii::$app->session->setFlash('error', \Yii::t('user', 'err_pwd_different'));
                } elseif (mb_strlen($post['pwd']) < $pwMinLen) {
                    $msg = \Yii::t('user', 'err_pwd_length');
                    \yii::$app->session->setFlash('error', str_replace('%MINLEN%', $pwMinLen, $msg));
                } else {
                    $user->pwdEnc = password_hash($post['pwd'], PASSWORD_DEFAULT);
                }
            }

            $user->save();

            if ($user->email != '' && $user->emailConfirmed) {
                if (isset($post['emailBlacklist'])) {
                    EMailBlacklist::addToBlacklist($user->email);
                } else {
                    EMailBlacklist::removeFromBlacklist($user->email);
                }
            }

            if ($post['email'] != '' && $post['email'] != $user->email) {
                /** @var AntragsgruenApp $params */
                $params = \Yii::$app->params;
                if ($params->confirmEmailAddresses) {
                    $changeRequested = $user->getChangeRequestedEmailAddress();
                    if ($changeRequested && $changeRequested == $post['email']) {
                        \yii::$app->session->setFlash('error', \Yii::t('user', 'err_emailchange_mail_sent'));
                    } elseif (filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
                        try {
                            $user->sendEmailChangeMail($post['email']);
                            \yii::$app->session->setFlash('success', \Yii::t('user', 'emailchange_sent'));
                        } catch (FormError $e) {
                            \yii::$app->session->setFlash('error', $e->getMessage());
                        } catch (MailNotSent $e) {
                            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                            \yii::$app->session->setFlash('error', $errMsg);
                        }
                    } else {
                        \yii::$app->session->setFlash('error', \Yii::t('user', 'err_invalid_email'));
                    }
                } else {
                    $user->changeEmailAddress($post['email'], '');
                    \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
                }
            } else {
                \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
            }
        }

        if ($this->isPostSet('accountDeleteConfirm') && $this->isPostSet('accountDelete')) {
            $user->deleteAccount();
            \yii::$app->user->logout(true);
            return $this->render('account_deleted');
        }

        if ($user->email != '' && $user->emailConfirmed) {
            $emailBlacklisted = EMailBlacklist::isBlacklisted($user->email);
        } else {
            $emailBlacklisted = false;
        }

        return $this->render('my_account', [
            'user'             => $user,
            'emailBlacklisted' => $emailBlacklisted,
            'pwMinLen'         => $pwMinLen,
        ]);
    }

    /**
     * @param string $code
     * @return string
     * @throws \Exception
     */
    public function actionEmailblacklist($code)
    {
        $user = User::getUserByUnsubscribeCode($code);
        if (!$user) {
            return $this->showErrorpage(403, \Yii::t('user', 'err_user_acode_notfound'));
        }

        if ($this->isPostSet('save')) {
            $post = \Yii::$app->request->post();
            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] == 'consultation') {
                $notis = UserNotification::getUserConsultationNotis($user, $this->consultation);
                foreach ($notis as $noti) {
                    $noti->delete();
                }
            }

            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] == 'all') {
                foreach ($user->notifications as $noti) {
                    $noti->delete();
                }
            }

            if (isset($post['emailBlacklist'])) {
                EMailBlacklist::addToBlacklist($user->email);
            } else {
                EMailBlacklist::removeFromBlacklist($user->email);
            }

            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        return $this->render('email_blacklist', [
            'user'          => $user,
            'consultation'  => $this->consultation,
            'isBlacklisted' => EMailBlacklist::isBlacklisted($user->email),
        ]);
    }

    /**
     * @return string
     */
    public function actionConsultationaccesserror()
    {
        return $this->render('@app/views/errors/consultation_access');
    }
}
