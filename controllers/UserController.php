<?php

namespace app\controllers;

use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, RedirectResponse, ResponseInterface};
use app\components\{Captcha, ConsultationAccessPassword, JwtCreator, RequestContext, SecondFactorAuthentication, Tools, UrlHelper};
use app\models\db\{AmendmentSupporter, EMailBlocklist, FailedLoginAttempt, MotionSupporter, User, UserConsultationScreening, UserNotification};
use app\models\events\UserEvent;
use app\models\exceptions\{ExceptionBase, FormError, Login, MailNotSent, ServerConfiguration};
use app\models\forms\LoginUsernamePasswordForm;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;
use yii\web\Response;

class UserController extends Base
{
    public const VIEW_ID_LOGIN_LOGIN = 'login';
    public const VIEW_ID_LOGIN_FORCE_PWD_CHANGE = 'login-force-pwd-change';
    public const VIEW_ID_LOGIN_FORCE_EMAIL_CONFIRM = 'confirmregistration';
    public const VIEW_ID_LOGIN_FORCE_2FA_REGISTRATION = 'login2fa-force-registration';
    public const VIEW_ID_LOGIN_2FA = 'login2fa';
    public const VIEW_ID_MYACCOUNT = 'myaccount';

    public $enableCsrfValidation = false;

    // Login and Mainainance mode is always allowed
    public ?bool $allowNotLoggedIn = true;

    private SecondFactorAuthentication $secondFactorAuthentication;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        $this->secondFactorAuthentication = new SecondFactorAuthentication(RequestContext::getSession());

        return $result;
    }

    protected function loginUser(User $user): void
    {
        RequestContext::getYiiUser()->login($user, $this->getParams()->autoLoginDuration);

        $user->dateLastLogin = date('Y-m-d H:i:s');
        $user->save();

        $authParts = explode(':', $user->auth);
        if (count($authParts) === 2) {
            FailedLoginAttempt::resetAfterSuccessfulLogin($authParts[1]);
        }
    }

    public function actionLogin(string $backUrl = ''): ResponseInterface
    {
        $this->layout = 'column2';

        if ($backUrl === '') {
            $backUrl = '/';
        }

        $usernamePasswordForm = new LoginUsernamePasswordForm(RequestContext::getSession(), User::getExternalAuthenticator());

        $conPwdConsultation = $this->consultation;
        if ($this->getHttpRequest()->get('passConId')) {
            foreach ($this->site->consultations as $consultation) {
                if ($consultation->urlPath === $this->getHttpRequest()->get('passConId')) {
                    $conPwdConsultation = $consultation;
                }
            }
        }

        if ($this->isPostSet('loginusernamepassword')) {
            $usernamePasswordForm->setAttributes($this->getHttpRequest()->post());
            try {
                $user = $usernamePasswordForm->getOrCreateUser($this->site);
                if ($user->status === User::STATUS_UNCONFIRMED && $this->getParams()->confirmEmailAddresses) {
                    // Needs to confirm e-mail-address before actually being logged in
                    $usernamePasswordForm->setLoggedInAwaitingEmailConfirmation($user);
                    $backUrl = UrlHelper::createUrl([
                        '/user/confirmregistration',
                        'backUrl' => $backUrl,
                        'email' => $user->email,
                    ]);
                } elseif ($user->getSettingsObj()->forcePasswordChange) {
                    // Needs to change password before actually being logged in
                    $usernamePasswordForm->setLoggedInAwaitingPasswordChange($user);
                    $backUrl = UrlHelper::createUrl([
                        '/user/login-force-pwd-change',
                        'backUrl' => $backUrl,
                    ]);
                } elseif ($return = $this->secondFactorAuthentication->onUsernamePwdLoginSuccess($user, $backUrl)) {
                    // Needs to perform 2FA before actually being logged in
                    return $return;
                } else {
                    $this->loginUser($user);
                    $this->getHttpSession()->setFlash('success', \Yii::t('user', 'welcome'));
                }

                /* 307 breaks user/NoEmailConfirmationCept
                $this->redirect($backUrl, 307);
                Yii::$app->end(307);
                */
                return new RedirectResponse($backUrl);
            } catch (Login $e) {
                $usernamePasswordForm->error = $e->getMessage();
            }
        }

        $conPwdErr = null;
        if ($this->isPostSet('loginconpwd') && $conPwdConsultation) {
            $conPwd = new ConsultationAccessPassword($conPwdConsultation);
            if ($conPwd->checkPassword($this->getHttpRequest()->post('password'))) {
                $conPwd->setCorrectCookie();
                return new RedirectResponse($backUrl);
            } else {
                $conPwdErr = \Yii::t('user', 'login_err_password');
            }
        }

        if ($this->getHttpSession()->isActive && $this->getHttpSession()->getFlash('error')) {
            $usernamePasswordForm->error = $this->getHttpSession()->getFlash('error');
            $this->getHttpSession()->removeFlash('error');
        }

        return new HtmlResponse($this->render(
            'login',
            [
                'backUrl'              => $backUrl,
                'usernamePasswordForm' => $usernamePasswordForm,
                'conPwdConsultation'   => $conPwdConsultation,
                'conPwdErr'            => $conPwdErr,
            ]
        ));
    }

    public function actionLogin2fa(string $backUrl = ''): ResponseInterface
    {
        $loggingInUser = $this->secondFactorAuthentication->getOngoingSessionUser();
        if (!$loggingInUser) {
            $minutes = SecondFactorAuthentication::TIMEOUT_2FA_SESSION / 60;
            $msg = str_replace('%minutes%', (string) $minutes, \Yii::t('user', 'err_2fa_timeout'));
            $this->getHttpSession()->setFlash('error', $msg);

            return new RedirectResponse(UrlHelper::createUrl('/user/login'));
        }

        if ($backUrl === '') {
            $backUrl = '/';
        }

        $error = null;
        if ($this->isPostSet('2fa') && trim($this->getPostValue('2fa'))) {
            if (Captcha::needsCaptcha($loggingInUser->email) && !Captcha::checkEnteredCaptcha($this->getRequestValue('captcha'))) {
                $error = \Yii::t('user', 'login_err_captcha');
                goto loginForm;
            }

            $successUser = $this->secondFactorAuthentication->confirmLoginWithSecondFactor($this->getPostValue('2fa'));
            if (!$successUser) {
                FailedLoginAttempt::logAttempt($loggingInUser->email);
                $error = \Yii::t('user', 'err_2fa_incorrect');
                goto loginForm;
            }

            $this->loginUser($successUser);
            $this->getHttpSession()->setFlash('success', \Yii::t('user', 'welcome'));

            return new RedirectResponse($backUrl);
        }

        $this->secondFactorAuthentication->getOngoingSessionUser();

        loginForm:
        $resp =  new HtmlResponse($this->render('login-2fa', [
            'captchaUsername' => $loggingInUser->email,
            'error' => $error,
        ]));

        $this->secondFactorAuthentication->getOngoingSessionUser();

        return $resp;
    }

    public function actionLogin2faForceRegistration(string $backUrl = ''): ResponseInterface
    {
        try {
            $loggingInUser = $this->secondFactorAuthentication->getForcedRegistrationUser();
        } catch (\Exception $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());

            return new RedirectResponse(UrlHelper::createUrl('/user/login'));
        }

        if ($backUrl === '') {
            $backUrl = '/';
        }

        $error = null;
        if ($this->isPostSet('set2fa') && trim($this->getPostValue('set2fa'))) {
            if (Captcha::needsCaptcha($loggingInUser->email) && !Captcha::checkEnteredCaptcha($this->getRequestValue('captcha'))) {
                $error = \Yii::t('user', 'login_err_captcha');
                goto loginForm;
            }

            try {
                $successUser = $this->secondFactorAuthentication->attemptForcedRegisteringSecondFactor(trim($this->getPostValue('set2fa')));
                $this->loginUser($successUser);
                $this->getHttpSession()->setFlash('success', \Yii::t('user', 'welcome'));

                return new RedirectResponse($backUrl);
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
                FailedLoginAttempt::logAttempt($loggingInUser->email);
            }
        }

        loginForm:
        return new HtmlResponse($this->render('login-2fa-force-registration', [
            'error' => $error,
            'captchaUsername' => $loggingInUser->email,
            'addSecondFactorKey' => $this->secondFactorAuthentication->createForcedRegistrationSecondFactor(),
        ]));
    }

    public function actionLoginForcePwdChange(string $backUrl = ''): ResponseInterface
    {
        if ($backUrl === '') {
            $backUrl = '/';
        }

        $usernamePasswordForm = new LoginUsernamePasswordForm(RequestContext::getSession(), User::getExternalAuthenticator());
        $sessionUser = $usernamePasswordForm->getOngoingPwdChangeSession();
        if (!$sessionUser) {
            return new RedirectResponse($backUrl);
        }

        $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

        $error = null;
        if ($this->isPostSet('change') && $this->getPostValue('pwd')) {
            if ($this->getPostValue('pwd') !== $this->getPostValue('pwd2')) {
                $error = \Yii::t('user', 'err_pwd_different');
            } elseif (grapheme_strlen($this->getPostValue('pwd')) < $pwMinLen) {
                $msg = \Yii::t('user', 'err_pwd_length');
                $error = str_replace('%MINLEN%', (string)$pwMinLen, $msg);
            } else {
                $sessionUser->pwdEnc = password_hash($this->getPostValue('pwd'), PASSWORD_DEFAULT);
                $settings = $sessionUser->getSettingsObj();
                $settings->forcePasswordChange = false;
                $sessionUser->setSettingsObj($settings);

                $this->loginUser($sessionUser);
                $this->getHttpSession()->setFlash('success', \Yii::t('user', 'welcome'));

                return new RedirectResponse($backUrl);
            }
        }

        return new HtmlResponse($this->render('login-force-pwd-change', [
            'pwMinLen' => $pwMinLen,
            'user' => $sessionUser,
            'error' => $error,
        ]));
    }

    public function actionToken(): JsonResponse
    {
        return new JsonResponse(JwtCreator::getJwtConfigForCurrUser($this->consultation));
    }

    public function actionConfirmregistration(string $backUrl = '', string $email = ''): ResponseInterface
    {
        $msgError = '';
        $prefillCode = '';
        $usernamePasswordForm = new LoginUsernamePasswordForm(RequestContext::getSession(), User::getExternalAuthenticator());
        $ongoingSessionUser = $usernamePasswordForm->getOngoingEmailConfirmationSessionUser();

        if ($this->isRequestSet('resend') && $ongoingSessionUser && !$ongoingSessionUser->hasTooRecentRecoveryEmail()) {
            $usernamePasswordForm->sendConfirmationEmail($ongoingSessionUser);
        } elseif ($this->isRequestSet('email') && $this->isRequestSet('code')) {
            /** @var User|null $user */
            $user = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            if (!$user) {
                $msgError = \Yii::t('user', 'err_email_acc_notfound');
            } elseif ($user->emailConfirmed === 1) {
                $msgError = \Yii::t('user', 'err_email_acc_confirmed');
            } elseif (Captcha::needsCaptcha($user->email) && !Captcha::checkEnteredCaptcha($this->getRequestValue('captcha'))) {
                $msgError = \Yii::t('user', 'login_err_captcha');
                $prefillCode = trim($this->getRequestValue('code', '')); // When coming from an e-mail, only ask for captcha
            } elseif ($user->checkEmailConfirmationCode(trim($this->getRequestValue('code')))) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $user->trigger(User::EVENT_ACCOUNT_CONFIRMED, new UserEvent($user));
                    if ($usernamePasswordForm->hasOngoingEmailConfirmationSession($user)) {
                        $this->loginUser($user);
                    }

                    if ($this->consultation && $this->consultation->getSettings()->managedUserAccounts) {
                        if ($this->consultation->getSettings()->allowRequestingAccess) {
                            UserConsultationScreening::askForConsultationPermission($user, $this->consultation);
                            $needsAdminScreening = true;
                        } else {
                            return new RedirectResponse(UrlHelper::createUrl('/user/consultationaccesserror', $this->consultation));
                        }
                    } else {
                        $needsAdminScreening = false;
                    }

                    return new HtmlResponse($this->render('registration_confirmed', ['needsAdminScreening' => $needsAdminScreening]));
                }
            } else {
                $msgError = \Yii::t('user', 'err_code_wrong');
                FailedLoginAttempt::logAttempt($user->email);
            }
        }

        return new HtmlResponse($this->render(
            'confirm_registration',
            [
                'email' => $email,
                'prefillCode' => $prefillCode,
                'errors' => $msgError,
                'backUrl' => $backUrl,
                'allowResend' => ($ongoingSessionUser && !$ongoingSessionUser->hasTooRecentRecoveryEmail()),
            ]
        ));
    }

    public function actionLogout(string $backUrl = ''): ResponseInterface
    {
        if ($backUrl === '') {
            $backUrl = $this->getHttpRequest()->post('backUrl', UrlHelper::homeUrl());
        }

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($loginProvider = $plugin::getDedicatedLoginProvider()) {
                try {
                    $pluginBackUrl = $loginProvider->logoutCurrentUserIfRelevant($backUrl);
                    if ($pluginBackUrl) {
                        return new RedirectResponse($pluginBackUrl, RedirectResponse::REDIRECT_TEMPORARY);
                    }
                } catch (\Exception $e) {
                    $msg = \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"';
                    return new HtmlErrorResponse(500, $msg);
                }
            }
        }

        RequestContext::getYiiUser()->logout();
        return new RedirectResponse($backUrl, RedirectResponse::REDIRECT_TEMPORARY);
    }

    public function actionRecovery(string $email = '', string $code = ''): HtmlResponse
    {
        if ($this->isPostSet('send')) {
            $email = $this->getRequestValue('email');
            /** @var User|null $user */
            $user = User::findOne(['auth' => 'email:' . $email]);

            if (Captcha::needsCaptcha($email) && !Captcha::checkEnteredCaptcha($this->getRequestValue('captcha'))) {
                $msg = \Yii::t('user', 'login_err_captcha');
                $this->getHttpSession()->setFlash('error', $msg);
            } elseif (!$user) {
                $msg = str_replace('%USER%', $email, \Yii::t('user', 'err_user_notfound'));
                $this->getHttpSession()->setFlash('error', $msg);
            } elseif ($user->getSettingsObj()->preventPasswordChange) {
                $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_pwd_fixed'));
            } else {
                try {
                    $user->sendRecoveryMail();
                    $msg = \Yii::t('user', 'pwd_recovery_sent');
                    $this->getHttpSession()->setFlash('success', $msg);
                } catch (MailNotSent | ServerConfiguration | FormError $e) {
                    $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    $this->getHttpSession()->setFlash('error', $errMsg);
                }
            }
        }

        if ($this->isPostSet('recover')) {
            $email = $this->getRequestValue('email');
            /** @var User|null $user */
            $user     = User::findOne(['auth' => 'email:' . $email]);
            $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

            if (Captcha::needsCaptcha($email) && !Captcha::checkEnteredCaptcha($this->getRequestValue('captcha'))) {
                $msg = \Yii::t('user', 'login_err_captcha');
                $this->getHttpSession()->setFlash('error', $msg);
            } elseif (!$user) {
                $msg = str_replace('%USER%', $email, \Yii::t('user', 'err_user_notfound'));
                $this->getHttpSession()->setFlash('error', $msg);
            } elseif (grapheme_strlen($this->getRequestValue('newPassword')) < $pwMinLen) {
                $msg = str_replace('%MINLEN%', (string)$pwMinLen, \Yii::t('user', 'err_pwd_length'));
                $this->getHttpSession()->setFlash('error', $msg);
            } else {
                try {
                    if ($user->checkRecoveryToken($this->getRequestValue('recoveryCode'))) {
                        $user->changePassword($this->getRequestValue('newPassword'));
                        return new HtmlResponse($this->render('recovery_confirmed'));
                    }
                } catch (ExceptionBase $e) {
                    $this->getHttpSession()->setFlash('error', $e->getMessage());
                }
            }
        }

        return new HtmlResponse($this->render('recovery', ['preEmail' => $email, 'preCode' => $code]));
    }

    public function actionEmailchange(string $email, string $code): RedirectResponse
    {
        $this->forceLogin();
        $user = User::getCurrentUser();
        try {
            $user->changeEmailAddress($email, $code);
            $this->getHttpSession()->setFlash('success', \Yii::t('user', 'emailchange_done'));
        } catch (FormError $e) {
            $this->getHttpSession()->setFlash('error', $e->getMessage());
        }
        return new RedirectResponse(UrlHelper::createUrl('user/myaccount'));
    }

    public function actionMyaccount(): HtmlResponse
    {
        $this->forceLogin();

        $user     = User::getCurrentUser();
        $pwMinLen = LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

        $params = AntragsgruenApp::getInstance();

        if ($this->isPostSet('resendEmailChange')) {
            $changeRequested = $user->getChangeRequestedEmailAddress();
            if ($changeRequested) {
                $lastRequest = time() - Tools::dateSql2timestamp($user->emailChangeAt);
                if ($lastRequest < 5 * 60) {
                    $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_emailchange_flood'));
                } else {
                    try {
                        $user->sendEmailChangeMail($changeRequested);
                        $this->getHttpSession()->setFlash('success', \Yii::t('user', 'emailchange_sent'));
                    } catch (MailNotSent | ServerConfiguration $e) {
                        $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                        $this->getHttpSession()->setFlash('error', $errMsg);
                    }
                }
            }
        }
        if ($this->isPostSet('save')) {
            $post = $this->getHttpRequest()->post();

            if (($user->fixedData & User::FIXED_NAME) === 0) {
                $user->nameGiven = $post['name_given'] ?? '';
                $user->nameFamily = $post['name_family'] ?? '';
                $user->name = trim($user->nameGiven . ' ' . $user->nameFamily);
            }

            $selectableOrganisations = $user->getSelectableUserOrganizations();
            if (isset($post['orgaPrimary']) && $selectableOrganisations) {
                foreach ($selectableOrganisations as $userGroup) {
                    if ($userGroup->id === intval($post['orgaPrimary'])) {
                        $user->organization = $userGroup->title;
                    }
                }
            }

            if (!$user->getSettingsObj()->preventPasswordChange && ($post['pwd'] !== '' || $post['pwd2'] !== '')) {
                if ($post['pwd'] !== $post['pwd2']) {
                    $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_pwd_different'));
                } elseif (grapheme_strlen($post['pwd']) < $pwMinLen) {
                    $msg = \Yii::t('user', 'err_pwd_length');
                    $this->getHttpSession()->setFlash('error', str_replace('%MINLEN%', (string)$pwMinLen, $msg));
                } else {
                    $user->pwdEnc = password_hash($post['pwd'], PASSWORD_DEFAULT);
                }
            }

            if ($user->supportsSecondFactorAuth() && isset($post['set2fa']) && trim($post['set2fa'])) {
                try {
                    $this->secondFactorAuthentication->attemptRegisteringSecondFactor($user, $post['set2fa']);
                } catch (\RuntimeException $e) {
                    $this->getHttpSession()->setFlash('error', $e->getMessage());
                }
            }

            if ($user->supportsSecondFactorAuth() && isset($post['remove2fa']) && trim($post['remove2fa'])) {
                $error = $this->secondFactorAuthentication->attemptRemovingSecondFactor($user, $post['remove2fa']);
                if ($error) {
                    $this->getHttpSession()->setFlash('error', $error);
                }
            }

            $user->save();

            if ($user->email && $user->emailConfirmed) {
                if (isset($post['emailBlocklist'])) {
                    EMailBlocklist::addToBlocklist($user->email);
                } else {
                    EMailBlocklist::removeFromBlocklist($user->email);
                }
            }

            if ($post['email'] != '' && $post['email'] != $user->email) {
                if ($params->confirmEmailAddresses) {
                    $changeRequested = $user->getChangeRequestedEmailAddress();
                    if ($changeRequested && $changeRequested == $post['email']) {
                        $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_emailchange_mail_sent'));
                    } elseif (filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
                        try {
                            $user->sendEmailChangeMail($post['email']);
                            $this->getHttpSession()->setFlash('success', \Yii::t('user', 'emailchange_sent'));
                        } catch (MailNotSent | ServerConfiguration $e) {
                            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                            $this->getHttpSession()->setFlash('error', $errMsg);
                        }
                    } else {
                        $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_invalid_email'));
                    }
                } else {
                    $user->changeEmailAddress($post['email'], '');
                    $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
                }
            } else {
                $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
            }
        }

        if ($this->isPostSet('accountDeleteConfirm') && $this->isPostSet('accountDelete') && $params->allowAccountDeletion) {
            $user->deleteAccount();
            RequestContext::getYiiUser()->logout(true);
            return new HtmlResponse($this->render('account_deleted'));
        }

        if ($user->email != '' && $user->emailConfirmed) {
            $emailBlocked = EMailBlocklist::isBlocked($user->email);
        } else {
            $emailBlocked = false;
        }

        if ($this->secondFactorAuthentication->userHasSecondFactorSetUp($user)) {
            $hasSecondFactor = true;
            $canRemoveSecondFactor = !$this->secondFactorAuthentication->isForcedToSetupSecondFactor($user);
            $addSecondFactorKey = null;
        } else {
            $hasSecondFactor = false;
            $canRemoveSecondFactor = false;
            $addSecondFactorKey = $this->secondFactorAuthentication->createSecondFactorKey($user);
        }

        return new HtmlResponse($this->render('my_account', [
            'user' => $user,
            'emailBlocked' => $emailBlocked,
            'pwMinLen' => $pwMinLen,
            'hasSecondFactor' => $hasSecondFactor,
            'canRemoveSecondFactor' => $canRemoveSecondFactor,
            'addSecondFactorKey' => $addSecondFactorKey,
        ]));
    }

    public function actionEmailblocklist(string $code): ResponseInterface
    {
        $user = User::getUserByUnsubscribeCode($code);
        if (!$user) {
            return new HtmlErrorResponse(403, \Yii::t('user', 'err_user_acode_notfound'));
        }

        if ($this->isPostSet('save')) {
            $post = $this->getHttpRequest()->post();
            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] === 'consultation') {
                $notis = UserNotification::getUserConsultationNotis($user, $this->consultation);
                foreach ($notis as $noti) {
                    $noti->delete();
                }
            }

            if (isset($post['unsubscribeOption']) && $post['unsubscribeOption'] === 'all') {
                foreach ($user->notifications as $noti) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $noti->delete();
                }
            }

            if (isset($post['emailBlocklist'])) {
                EMailBlocklist::addToBlocklist($user->email);
            } else {
                EMailBlocklist::removeFromBlocklist($user->email);
            }

            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        return new HtmlResponse($this->render('email_blocklist', [
            'isBlocked' => EMailBlocklist::isBlocked($user->email),
        ]));
    }

    public function actionDataExport(): JsonResponse
    {
        $this->forceLogin();
        $user = User::getCurrentUser();

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

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
            if (!$comment->getIMotion() || $comment->getIMotion()->isDeleted()) {
                continue;
            }
            $data['comments'][] = $comment->getUserdataExportObject();
        }

        foreach ($user->amendmentComments as $comment) {
            if (!$comment->getIMotion() || $comment->getIMotion()->isDeleted()) {
                continue;
            }
            $data['comments'][] = $comment->getUserdataExportObject();
        }

        return new JsonResponse($data);
    }

    public function actionConsultationaccesserror(): HtmlResponse
    {
        $user = User::getCurrentUser();

        if ($this->isPostSet('askPermission') && $this->consultation->getSettings()->allowRequestingAccess) {
            UserConsultationScreening::askForConsultationPermission($user, $this->consultation);
            $this->consultation->refresh();
        }

        if ($user) {
            $askForPermission   = $this->consultation->getSettings()->allowRequestingAccess;
            $askedForPermission = false;
            foreach ($this->consultation->screeningUsers as $screening) {
                if ($screening->userId === $user->id) {
                    $askForPermission   = false;
                    $askedForPermission = true;
                }
            }
        } else {
            $askForPermission   = false;
            $askedForPermission = false;
        }

        return new HtmlResponse($this->render('@app/views/errors/consultation_access', [
            'askForPermission'   => $askForPermission,
            'askedForPermission' => $askedForPermission,
        ]));
    }
}
