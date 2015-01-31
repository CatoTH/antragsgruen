<?php

namespace app\controllers;

use app\components\WurzelwerkAuthClient;
use app\models\db\Site;
use app\models\db\User;
use Yii;
use yii\helpers\Html;

class UserController extends Base
{
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
}
