<?php
namespace app\commands;

use app\models\db\User;
use yii\console\Controller;

/**
 * Function to add, remove and modify registered users
 * @package app\commands
 */
class UserController extends Controller
{

    public function actionSetPassword($auth, $password)
    {
        if (mb_strpos($auth, ':') === false) {
            if (mb_strpos($auth, '@') !== false) {
                $auth = 'email:' . $auth;
            } else {
                $auth = User::wurzelwerkId2Auth($auth);
            }
        }
        /** @var User $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $this->stderr('User not found: ' . $auth . "\n");
            return;
        }

        $user->changePassword($password);
        $this->stdout('The password has been changed.' . "\n");
    }
}
