<?php

namespace app\controllers;

use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, RedirectResponse, ResponseInterface};
use app\components\{Captcha, ConsultationAccessPassword, JwtCreator, RequestContext, Tools, UrlHelper};
use app\models\db\{AmendmentSupporter, EMailBlocklist, FailedLoginAttempt, MotionSupporter, User, UserConsultationScreening, UserNotification};
use app\models\events\UserEvent;
use app\models\exceptions\{ExceptionBase, FormError, Login, MailNotSent, ServerConfiguration};
use app\models\forms\LoginUsernamePasswordForm;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;
use yii\web\Response;

class UserController extends Base
{
    public $enableCsrfValidation = false;

    // Login and Mainainance mode is always allowed
    public ?bool $allowNotLoggedIn = true;

    protected function loginUser(User $user): void
    {
        RequestContext::getUser()->login($user, $this->getParams()->autoLoginDuration);

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

        $usernamePasswordForm = new LoginUsernamePasswordForm(User::getExternalAuthenticator());

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
                $this->loginUser($user);

                $unconfirmed = ($user->status === User::STATUS_UNCONFIRMED);
                if ($unconfirmed && $this->getParams()->confirmEmailAddresses) {
                    $backUrl = UrlHelper::createUrl([
                        'user/confirmregistration',
                        'backUrl' => $backUrl,
                        'email'   => $user->email,
                    ]);
                } else {
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
                $conPwdErr = 'Invalid password';
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

    public function actionToken(): JsonResponse
    {
        return new JsonResponse(JwtCreator::getJwtConfigForCurrUser($this->consultation));
    }

    public function actionConfirmregistration(string $backUrl = '', string $email = ''): HtmlResponse
    {
        $msgError = '';

        if ($this->isRequestSet('email') && $this->isRequestSet('code')) {
            /** @var User|null $user */
            $user = User::findOne(['auth' => 'email:' . $this->getRequestValue('email')]);
            if (!$user) {
                $msgError = \Yii::t('user', 'err_email_acc_notfound');
            } elseif ($user->emailConfirmed === 1) {
                $msgError = \Yii::t('user', 'err_email_acc_confirmed');
            } elseif ($user->checkEmailConfirmationCode(trim($this->getRequestValue('code')))) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $user->trigger(User::EVENT_ACCOUNT_CONFIRMED, new UserEvent($user));
                    $this->loginUser($user);

                    if ($this->consultation && $this->consultation->getSettings()->managedUserAccounts) {
                        UserConsultationScreening::askForConsultationPermission($user, $this->consultation);
                        $needsAdminScreening = true;
                    } else {
                        $needsAdminScreening = false;
                    }

                    return new HtmlResponse($this->render('registration_confirmed', ['needsAdminScreening' => $needsAdminScreening]));
                }
            } else {
                $msgError = \Yii::t('user', 'err_code_wrong');
            }
        }

        return new HtmlResponse($this->render(
            'confirm_registration',
            [
                'email'   => $email,
                'errors'  => $msgError,
                'backUrl' => $backUrl
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

        RequestContext::getUser()->logout();
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
            $user->nameGiven = $post['name_given'] ?? '';
            $user->nameFamily = $post['name_family'] ?? '';
            $user->name = trim($user->nameGiven . ' ' . $user->nameFamily);

            $selectableOrganisations = $user->getSelectableUserOrganizations();
            if (isset($post['orgaPrimary']) && $selectableOrganisations) {
                foreach ($selectableOrganisations as $userGroup) {
                    if ($userGroup->id === intval($post['orgaPrimary'])) {
                        $user->organization = $userGroup->title;
                    }
                }
            }

            if ($post['pwd'] !== '' || $post['pwd2'] !== '') {
                if ($post['pwd'] !== $post['pwd2']) {
                    $this->getHttpSession()->setFlash('error', \Yii::t('user', 'err_pwd_different'));
                } elseif (grapheme_strlen($post['pwd']) < $pwMinLen) {
                    $msg = \Yii::t('user', 'err_pwd_length');
                    $this->getHttpSession()->setFlash('error', str_replace('%MINLEN%', (string)$pwMinLen, $msg));
                } else {
                    $user->pwdEnc = password_hash($post['pwd'], PASSWORD_DEFAULT);
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
                $params = AntragsgruenApp::getInstance();
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

        if ($this->isPostSet('accountDeleteConfirm') && $this->isPostSet('accountDelete')) {
            $user->deleteAccount();
            RequestContext::getUser()->logout(true);
            return new HtmlResponse($this->render('account_deleted'));
        }

        if ($user->email != '' && $user->emailConfirmed) {
            $emailBlocked = EMailBlocklist::isBlocked($user->email);
        } else {
            $emailBlocked = false;
        }

        return new HtmlResponse($this->render('my_account', [
            'user' => $user,
            'emailBlocked' => $emailBlocked,
            'pwMinLen' => $pwMinLen,
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

        if ($this->isPostSet('askPermission')) {
            UserConsultationScreening::askForConsultationPermission($user, $this->consultation);
            $this->consultation->refresh();
        }

        if ($user) {
            $askForPermission   = true;
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
