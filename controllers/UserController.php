<?php

namespace app\controllers;

use app\components\ConsultationAccessPassword;
use app\components\Tools;
use app\components\UrlHelper;
use app\components\WurzelwerkSamlClient;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationUserPrivilege;
use app\models\db\EMailBlacklist;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\events\UserEvent;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\FormError;
use app\models\exceptions\Login;
use app\models\exceptions\MailNotSent;
use app\models\forms\LoginUsernamePasswordForm;
use app\models\settings\AntragsgruenApp;
use Yii;
use yii\helpers\Html;
use yii\web\Cookie;
use yii\web\Response;

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
     * @throws \yii\base\ExitException
     */
    public function actionLoginsaml($backUrl = '')
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;
        if (!$params->isSamlActive()) {
            return 'SAML is not supported';
        }

        if ($backUrl == '') {
            $backUrl = Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        try {
            $samlClient = new WurzelwerkSamlClient();
            $samlClient->requireAuth();

            $this->loginUser($samlClient->getOrCreateUser());

            $this->redirect($backUrl);
        } catch (\Exception $e) {
            return $this->showErrorpage(
                500,
                Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }

        return '';
    }

    /**
     * @param string $backUrl
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionLogin($backUrl = '')
    {
        $this->layout = 'column2';

        if ($backUrl === '') {
            $backUrl = '/';
        }

        $usernamePasswordForm = new LoginUsernamePasswordForm();

        $conPwdConsultation = $this->consultation;
        if (Yii::$app->request->get('passConId')) {
            foreach ($this->site->consultations as $consultation) {
                if ($consultation->urlPath === Yii::$app->request->get('passConId')) {
                    $conPwdConsultation = $consultation;
                }
            }
        }

        if ($this->isPostSet('loginusernamepassword')) {
            $usernamePasswordForm->setAttributes(Yii::$app->request->post());
            try {
                $user = $usernamePasswordForm->getOrCreateUser($this->site);
                $this->loginUser($user);

                $unconfirmed = ($user->status === User::STATUS_UNCONFIRMED);
                if ($unconfirmed && $this->getParams()->confirmEmailAddresses) {
                    $backUrl = UrlHelper::createUrl([
                        'user/confirmregistration',
                        'backUrl' => $backUrl,
                        'email'   => $user->email,
                    ]);
                } else {
                    Yii::$app->session->setFlash('success', Yii::t('user', 'welcome'));
                }

                /* 307 breaks user/NoEmailConfirmationCept
                $this->redirect($backUrl, 307);
                Yii::$app->end(307);
                */
                $this->redirect($backUrl);
                Yii::$app->end(302);
            } catch (Login $e) {
                $usernamePasswordForm->error = $e->getMessage();
            }
        }

        $conPwdErr = null;
        if ($this->isPostSet('loginconpwd') && $conPwdConsultation) {
            $conPwd = new ConsultationAccessPassword($conPwdConsultation);
            if ($conPwd->checkPassword(Yii::$app->request->post('password'))) {
                $conPwd->setCorrectCookie();
                $this->redirect($backUrl);
                Yii::$app->end(302);
            } else {
                $conPwdErr = 'Invalid password';
            }
        }

        if (Yii::$app->session->isActive && Yii::$app->session->getFlash('error')) {
            $usernamePasswordForm->error = Yii::$app->session->getFlash('error');
            Yii::$app->session->removeFlash('error');
        }


        return $this->render(
            'login',
            [
                'backUrl'              => $backUrl,
                'usernamePasswordForm' => $usernamePasswordForm,
                'conPwdConsultation'   => $conPwdConsultation,
                'conPwdErr'            => $conPwdErr,
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
                $msgError = Yii::t('user', 'err_email_acc_notfound');
            } elseif ($user->checkEmailConfirmationCode(trim($this->getRequestValue('code')))) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $user->trigger(User::EVENT_ACCOUNT_CONFIRMED, new UserEvent($user));
                    $this->loginUser($user);

                    if ($this->consultation && $this->consultation->getSettings()->managedUserAccounts) {
                        ConsultationUserPrivilege::askForConsultationPermission($user, $this->consultation);
                        $needsAdminScreening = true;
                    } else {
                        $needsAdminScreening = false;
                    }

                    return $this->render('registration_confirmed', ['needsAdminScreening' => $needsAdminScreening]);
                }
            } else {
                $msgError = Yii::t('user', 'err_code_wrong');
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
     * @throws \yii\base\ExitException
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
                Yii::$app->user->logout();
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
                Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
    }


    /**
     * @param string $backUrl
     * @return int|string
     * @throws \yii\base\ExitException
     */
    public function actionLogout($backUrl)
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;

        if ($backUrl == '') {
            $backUrl = Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        if ($params->isSamlActive()) {
            return $this->logoutSaml($backUrl);
        } else {
            Yii::$app->user->logout();
            $this->redirect($backUrl, 307);
            return '';
        }
    }

    /**
     * @param string $email
     * @param string $code
     * @return string
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public function actionRecovery($email = '', $code = '')
    {
        if ($this->isPostSet('send')) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            if (!$user) {
                $msg = str_replace('%USER%', $this->getRequestValue('email'), Yii::t('user', 'err_user_notfound'));
                Yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $this->getRequestValue('email');
                try {
                    $user->sendRecoveryMail();
                    $msg = Yii::t('user', 'pwd_recovery_sent');
                    Yii::$app->session->setFlash('success', $msg);
                } catch (MailNotSent $e) {
                    $errMsg = Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    Yii::$app->session->setFlash('error', $errMsg);
                } catch (FormError $e) {
                    $errMsg = Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    Yii::$app->session->setFlash('error', $errMsg);
                }
            }
        }

        if ($this->isPostSet('recover')) {
            /** @var User $user */
            $user     = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;
            if (!$user) {
                $msg = str_replace('%USER%', $this->getRequestValue('email'), Yii::t('user', 'err_user_notfound'));
                Yii::$app->session->setFlash('error', $msg);
            } elseif (mb_strlen($this->getRequestValue('newPassword')) < $pwMinLen) {
                $msg = str_replace('%MINLEN%', $pwMinLen, Yii::t('user', 'err_pwd_length'));
                Yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $this->getRequestValue('email');
                try {
                    if ($user->checkRecoveryToken($this->getRequestValue('recoveryCode'))) {
                        $user->changePassword($this->getRequestValue('newPassword'));
                        return $this->render('recovery_confirmed');
                    }
                } catch (ExceptionBase $e) {
                    Yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('recovery', ['preEmail' => $email, 'preCode' => $code]);
    }

    /**
     * @param string $email
     * @param string $code
     * @return \yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionEmailchange($email, $code)
    {
        $this->forceLogin();
        $user = User::getCurrentUser();
        try {
            $user->changeEmailAddress($email, $code);
            Yii::$app->session->setFlash('success', Yii::t('user', 'emailchange_done'));
        } catch (FormError $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(UrlHelper::createUrl('user/myaccount'));
    }

    /**
     * @return string
     * @throws FormError
     * @throws \app\models\exceptions\ServerConfiguration
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
                    Yii::$app->session->setFlash('error', Yii::t('user', 'err_emailchange_flood'));
                } else {
                    try {
                        $user->sendEmailChangeMail($changeRequested);
                        Yii::$app->session->setFlash('success', Yii::t('user', 'emailchange_sent'));
                    } catch (MailNotSent $e) {
                        $errMsg = Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                        Yii::$app->session->setFlash('error', $errMsg);
                    }
                }
            }
        }
        if ($this->isPostSet('save')) {
            $post = Yii::$app->request->post();
            if (trim($post['name']) != '') {
                $user->name = $post['name'];
            }

            if ($post['pwd'] != '' || $post['pwd2'] != '') {
                if ($post['pwd'] != $post['pwd2']) {
                    Yii::$app->session->setFlash('error', Yii::t('user', 'err_pwd_different'));
                } elseif (mb_strlen($post['pwd']) < $pwMinLen) {
                    $msg = Yii::t('user', 'err_pwd_length');
                    Yii::$app->session->setFlash('error', str_replace('%MINLEN%', $pwMinLen, $msg));
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
                $params = Yii::$app->params;
                if ($params->confirmEmailAddresses) {
                    $changeRequested = $user->getChangeRequestedEmailAddress();
                    if ($changeRequested && $changeRequested == $post['email']) {
                        Yii::$app->session->setFlash('error', Yii::t('user', 'err_emailchange_mail_sent'));
                    } elseif (filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
                        try {
                            $user->sendEmailChangeMail($post['email']);
                            Yii::$app->session->setFlash('success', Yii::t('user', 'emailchange_sent'));
                        } catch (MailNotSent $e) {
                            $errMsg = Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                            Yii::$app->session->setFlash('error', $errMsg);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', Yii::t('user', 'err_invalid_email'));
                    }
                } else {
                    $user->changeEmailAddress($post['email'], '');
                    Yii::$app->session->setFlash('success', Yii::t('base', 'saved'));
                }
            } else {
                Yii::$app->session->setFlash('success', Yii::t('base', 'saved'));
            }
        }

        if ($this->isPostSet('accountDeleteConfirm') && $this->isPostSet('accountDelete')) {
            $user->deleteAccount();
            Yii::$app->user->logout(true);
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
     * @throws \Throwable
     */
    public function actionEmailblacklist($code)
    {
        $user = User::getUserByUnsubscribeCode($code);
        if (!$user) {
            return $this->showErrorpage(403, Yii::t('user', 'err_user_acode_notfound'));
        }

        if ($this->isPostSet('save')) {
            $post = Yii::$app->request->post();
            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] === 'consultation') {
                $notis = UserNotification::getUserConsultationNotis($user, $this->consultation);
                foreach ($notis as $noti) {
                    $noti->delete();
                }
            }

            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] === 'all') {
                foreach ($user->notifications as $noti) {
                    $noti->delete();
                }
            }

            if (isset($post['emailBlacklist'])) {
                EMailBlacklist::addToBlacklist($user->email);
            } else {
                EMailBlacklist::removeFromBlacklist($user->email);
            }

            Yii::$app->session->setFlash('success', Yii::t('base', 'saved'));
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
    public function actionDataExport()
    {
        $this->forceLogin();
        $user = User::getCurrentUser();

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/json');

        $data = [
            'user'                 => $user->getUserdataExportObject(),
            'motions'              => [],
            'amendments'           => [],
            'supported_motions'    => [],
            'supported_amendments' => [],
            'comments'             => [],
            'sent_emails'          => [],
        ];

        foreach ($user->motionSupports as $motionSupport) {
            if (!$motionSupport->motion || $motionSupport->motion->isDeleted()) {
                continue;
            }
            if ($motionSupport->role === MotionSupporter::ROLE_INITIATOR) {
                $data['motions'][] = $motionSupport->motion->getUserdataExportObject();
            } else {
                $data['supported_motions'][] = [
                    'type'                 => $motionSupport->role,
                    'url'                  => $motionSupport->motion->getLink(true),
                    'title'                => $motionSupport->motion->title,
                    'support_name'         => $motionSupport->name,
                    'support_organization' => $motionSupport->organization,
                    'contact_name'         => $motionSupport->contactName,
                    'contact_email'        => $motionSupport->contactEmail,
                    'contact_phone'        => $motionSupport->contactPhone,
                ];
            }
        }

        foreach ($user->amendmentSupports as $amendmentSupport) {
            if (!$amendmentSupport->amendment || $amendmentSupport->amendment->isDeleted()) {
                continue;
            }
            if ($amendmentSupport->role === AmendmentSupporter::ROLE_INITIATOR) {
                $data['amendments'][] = $amendmentSupport->amendment->getUserdataExportObject();
            } else {
                $data['supported_amendments'][] = [
                    'type'                 => $amendmentSupport->role,
                    'url'                  => $amendmentSupport->amendment->getLink(true),
                    'title'                => $amendmentSupport->amendment->getTitle(),
                    'support_name'         => $amendmentSupport->name,
                    'support_organization' => $amendmentSupport->organization,
                    'contact_name'         => $amendmentSupport->contactName,
                    'contact_email'        => $amendmentSupport->contactEmail,
                    'contact_phone'        => $amendmentSupport->contactPhone,
                ];
            }
        }

        foreach ($user->motionComments as $comment) {
            if (!$comment->motion || $comment->motion->isDeleted()) {
                continue;
            }
            $data['comments'][] = $comment->getUserdataExportObject();
        }

        foreach ($user->amendmentComments as $comment) {
            if (!$comment->amendment || $comment->amendment->isDeleted()) {
                continue;
            }
            $data['comments'][] = $comment->getUserdataExportObject();
        }

        return json_encode($data);
    }

    /**
     * @return string
     */
    public function actionConsultationaccesserror()
    {
        $user = User::getCurrentUser();

        if ($this->isPostSet('askPermission')) {
            ConsultationUserPrivilege::askForConsultationPermission($user, $this->consultation);
            $this->consultation->refresh();
        }

        if ($user) {
            $askForPermission   = true;
            $askedForPermission = false;
            foreach ($this->consultation->userPrivileges as $privilege) {
                if ($privilege->userId === $user->id) {
                    $askForPermission   = false;
                    $askedForPermission = true;
                }
            }
        } else {
            $askForPermission   = false;
            $askedForPermission = false;
        }

        return $this->render('@app/views/errors/consultation_access', [
            'askForPermission'   => $askForPermission,
            'askedForPermission' => $askedForPermission,
        ]);
    }
}
