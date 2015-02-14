<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\WurzelwerkAuthClient;
use app\models\db\User;
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
        echo "Hallo!";
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
     * @param string $login_goto
     * @return int|string
     */
    public function actionLoginwurzelwerk($login_goto = "")
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
                $this->redirect($login_goto);
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
     * @return string
     */
    public function actionLogin($subdomain = '', $consultationPath = '', $backUrl = '')
    {
        $this->layoutParams->twocols = true;
        $this->layout                = 'column2';

        $this->loadConsultation($subdomain, $consultationPath);

        $err_str = "";

        

        return $this->render(
            'login',
            [
                //"model"   => $model,
                "msg_err" => $err_str,
            ]
        );
    }

    /**
     *
     */
    public function actionLogout()
    {
        // @TODO
    }
}
