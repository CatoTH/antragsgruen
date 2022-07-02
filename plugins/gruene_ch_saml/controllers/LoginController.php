<?php

namespace app\plugins\gruene_ch_saml\controllers;

use app\components\{RequestContext, UrlHelper};
use app\controllers\Base;
use app\models\db\User;
use SimpleSAML\Auth\Simple;
use yii\helpers\Html;

class LoginController extends Base
{
    const PARAM_EMAIL = 'gmnMail';
    const PARAM_USERNAME = 'uid';
    const PARAM_GIVEN_NAME = 'givenName';
    const PARAM_FAMILY_NAME = 'sn';

    public $enableCsrfValidation = false;

    // Login and Mainainance mode is always allowed
    public $allowNotLoggedIn = true;

    /**
     * @throws \Exception
     */
    private function getOrCreateUser(array $params): User
    {
        var_dump($params);
        die();
        $email = $this->params[static::PARAM_EMAIL][0];
        $givenname = (isset($this->params[static::PARAM_GIVEN_NAME]) ? $this->params[static::PARAM_GIVEN_NAME][0] : '');
        $familyname = (isset($this->params[static::PARAM_FAMILY_NAME]) ? $this->params[static::PARAM_FAMILY_NAME][0] : '');
        $username = $this->params[static::PARAM_USERNAME][0];
        $auth = User::gruenesNetzId2Auth($username);

        /** @var User $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $user = new User();
        }

        $user->name = $givenname . ' ' . $familyname;
        $user->nameGiven = $givenname;
        $user->nameFamily = $familyname;
        $user->email = $email;
        $user->emailConfirmed = 1;
        $user->fixedData = 1;
        $user->auth = $auth;
        $user->status = User::STATUS_CONFIRMED;
        $user->organization = '';
        if (!$user->save()) {
            throw new \Exception('Could not create user');
        }

        $this->syncUserGroups($user, $organizations);

        return $user;
    }

    public function actionLogin(string $backUrl = ''): void
    {
        if ($backUrl == '') {
            $backUrl = \Yii::$app->request->post('backUrl', UrlHelper::homeUrl());
        }

        try {
            $samlClient = new Simple('gruene-ch');

            $samlClient->requireAuth([]);
            if (!$samlClient->isAuthenticated()) {
                throw new \Exception('SimpleSaml: Something went wrong on requireAuth');
            }
            $params = $samlClient->getAttributes();

            $user = $this->getOrCreateUser($params);
            RequestContext::getUser()->login($user, $this->getParams()->autoLoginDuration);

            $user->dateLastLogin = date('Y-m-d H:i:s');
            $user->save();

            $this->redirect($backUrl);
        } catch (\Exception $e) {
            $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
    }
}
