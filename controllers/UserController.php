<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\WurzelwerkAuthClient;
use app\models\db\User;
use app\models\exceptions\Login;
use app\models\forms\LoginUsernamePasswordForm;
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
    public function actionLoginwurzelwerk($backUrl = "")
    {
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
        $this->loadConsultation($subdomain, $consultationPath);

        $usernamePasswordForm = new LoginUsernamePasswordForm();

        if (isset($_POST["loginusernamepassword"])) {
            $usernamePasswordForm->setAttributes($_POST);
            try {
                $user = $usernamePasswordForm->getOrCreateUser($this->site);
                $this->loginUser($user);
                $this->redirect($backUrl, 307);
            } catch (Login $e) {
            }
        }


        return $this->render(
            'login',
            [
                "usernamePasswordForm" => $usernamePasswordForm,
            ]
        );
    }

    /**
     *
     */
    public function actionConfirmregistration()
    {
        // @TODO
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
