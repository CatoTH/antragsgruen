<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\UrlHelper;
use app\components\WurzelwerkAuthClient;
use app\components\WurzelwerkAuthClientTest;
use app\models\db\EMailBlacklist;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\exceptions\ExceptionBase;
use app\models\exceptions\Login;
use app\models\forms\LoginUsernamePasswordForm;
use app\models\settings\AntragsgruenApp;
use Yii;

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
     * @param int $login
     * @param string $login_sec
     * @param string $redirect
     */
    public function actionLoginbyredirecttoken($login, $login_sec, $redirect)
    {
        if ($login_sec == AntiXSS::createToken($login)) {
            /** @var User $user */
            $user = User::findOne($login);
            if (!$user) {
                die('User not found');
            }
            Yii::$app->user->login($user, $this->getParams()->autoLoginDuration);
            $this->redirect($redirect);
        } else {
            die('Invalid Code');
        }
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
            $backUrl = (isset($_POST['backUrl']) ? $_POST['backUrl'] : UrlHelper::homeUrl());
        }

        if (YII_ENV == 'test') {
            $client = new WurzelwerkAuthClientTest();
            if (isset($_REQUEST['username'])) {
                $client->setClaimedId($_REQUEST['username']);
                $this->redirect($client->getFakeRedirectUrl($backUrl));
                return '';
            }
        } else {
            $client = new WurzelwerkAuthClient();
        }

        if (isset($_REQUEST['openid_claimed_id'])) {
            $client->setClaimedId($_REQUEST['openid_claimed_id']);
        } elseif (isset($_REQUEST['username'])) {
            $client->setClaimedId($_REQUEST['username']);
        }

        if (isset($_REQUEST['openid_mode'])) {
            if ($_REQUEST['openid_mode'] == 'error') {
                \yii::$app->session->setFlash('error', 'An error occurred: ' . $_REQUEST['openid_error']);
                return $this->actionLogin($backUrl);
            } elseif ($_REQUEST['openid_mode'] == 'cancel') {
                \yii::$app->session->setFlash('error', 'Login abgebrochen');
                return $this->actionLogin($backUrl);
            } elseif ($client->validate()) {
                $this->loginUser($client->getOrCreateUser());
                $this->redirect($backUrl);
            } else {
                $msg = 'Es trat ein unbekannter Fehler auf.' . "\n" .
                    'Falls du versucht hast, dich mit deinen Wurzelwerk-Zugangsdaten einzuloggen, ' .
                    'versuch es einfach noch ein zweites Mal - möglicherweise war das nur ' .
                    'ein temporärer Fehler seitens des Wurzelwerks.';
                \yii::$app->session->setFlash('error', $msg);
                return $this->actionLogin($backUrl);
            }
            return '';
        }

        $url = $client->buildAuthUrl();
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

        if (isset($_POST['loginusernamepassword'])) {
            $usernamePasswordForm->setAttributes($_POST);
            try {
                $user = $usernamePasswordForm->getOrCreateUser($this->site);
                $this->loginUser($user);

                $unconfirmed = $user->status == User::STATUS_UNCONFIRMED;
                if ($unconfirmed && $this->getParams()->confirmEmailAddresses) {
                    $backUrl = UrlHelper::createUrl(
                        [
                            'user/confirmregistration',
                            'backUrl' => $backUrl,
                            'email'   => $user->email,
                        ]
                    );
                } else {
                    \Yii::$app->session->setFlash('success', 'Willkommen!');
                }

                $this->redirect($backUrl, 307);
                \Yii::$app->end(307);
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

        if (isset($_REQUEST['email']) && isset($_REQUEST['code'])) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $_REQUEST['email']]);
            if (!$user) {
                $msgError = 'Es existiert kein Zugang mit der angegebenen E-Mail-Adresse...?';
            } elseif ($user->checkEmailConfirmationCode($_REQUEST['code'])) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $this->loginUser($user);
                    return $this->render('registration_confirmed');
                }
            } else {
                $msgError = 'Der angegebene Code stimmt leider nicht.';
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
     */
    public function actionLogout($backUrl)
    {
        \Yii::$app->user->logout();
        $this->redirect($backUrl, 307);
    }

    /**
     * @param string $email
     * @param string $code
     * @return string
     */
    public function actionRecovery($email = '', $code = '')
    {
        if (isset($_POST['send'])) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $_REQUEST['email']]);
            if (!$user) {
                $msg = str_replace('%USER%', $_REQUEST['email'], 'Der Account %USER% wurde nicht gefunden.');
                \yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $_REQUEST['email'];
                try {
                    $user->sendRecoveryMail();
                    $msg = 'Dir wurde eine Passwort-Wiederherstellungs-Mail geschickt.';
                    \yii::$app->session->setFlash('success', $msg);
                } catch (ExceptionBase $e) {
                    \yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
        }

        if (isset($_POST['recover'])) {
            /** @var User $user */
            $user     = User::findOne(['auth' => 'email:' . $_REQUEST['email']]);
            $pwMinLen = \app\models\forms\LoginUsernamePasswordForm::PASSWORD_MIN_LEN;
            if (!$user) {
                $msg = str_replace('%USER%', $_REQUEST['email'], 'Der Account %USER% wurde nicht gefunden.');
                \yii::$app->session->setFlash('error', $msg);
            } elseif (mb_strlen($_POST['newPassword']) < $pwMinLen) {
                $msg = str_replace('%MINLEN%', $pwMinLen, 'Das Passwort muss mindestens %MINLEN% Zeichen lang sein.');
                \yii::$app->session->setFlash('error', $msg);
            } else {
                $email = $_REQUEST['email'];
                try {
                    if ($user->checkRecoveryToken($_REQUEST['recoveryCode'])) {
                        $user->changePassword($_REQUEST['newPassword']);
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
     * @return string
     */
    public function actionMyaccount()
    {
        $this->forceLogin();

        $user     = User::getCurrentUser();
        $pwMinLen = \app\models\forms\LoginUsernamePasswordForm::PASSWORD_MIN_LEN;

        if (isset($_POST['save'])) {
            if (trim($_POST['name']) != '') {
                if ($user->name != $_POST['name']) {
                }
                $user->name = $_POST['name'];
            }

            if ($_POST['pwd'] != '' || $_POST['pwd2'] != '') {
                if ($_POST['pwd'] != $_POST['pwd2']) {
                    \yii::$app->session->setFlash('error', 'Die beiden Passwörter stimmen nicht überein.');
                } elseif (mb_strlen($_POST['pwd']) < $pwMinLen) {
                    $msg = 'Das Passwort muss mindestens %MINLEN% Zeichen lang sein.';
                    \yii::$app->session->setFlash('error', str_replace('%MINLEN%', $pwMinLen, $msg));
                } else {
                    $user->pwdEnc = password_hash($_POST['pwd'], PASSWORD_DEFAULT);
                }
            }

            $user->save();

            if ($user->email != '' && $user->emailConfirmed) {
                if (isset($_POST['emailBlcklist'])) {
                    EMailBlacklist::addToBlacklist($user->email);
                } else {
                    EMailBlacklist::removeFromBlacklist($user->email);
                }
            }

            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        if (isset($_POST['accountDeleteConfirm']) && isset($_POST['accountDelete'])) {
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
     * @return string
     */
    public function actionEmailblacklist($code)
    {
        $user = User::getUserByUnsubscribeCode($code);
        if (!$user) {
            return $this->showErrorpage(403, 'BenutzerIn nicht gefunden / Ungültiger Code');
        }

        if (isset($_POST['save'])) {
            if (isset($_POST['unsubscribeOption']) && $_POST['unsubscribeOption'] == 'consultation') {
                $notis = UserNotification::getUserConsultationNotis($user, $this->consultation);
                foreach ($notis as $noti) {
                    $noti->delete();
                }
            }

            if (isset($_POST['unsubscribeOption']) && $_POST['unsubscribeOption'] == 'all') {
                foreach ($user->notifications as $noti) {
                    $noti->delete();
                }
            }

            if (isset($_POST['emailBlacklist'])) {
                EMailBlacklist::addToBlacklist($user->email);
            } else {
                EMailBlacklist::removeFromBlacklist($user->email);
            }

            \yii::$app->session->setFlash('success', 'Gespeichert.');
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
