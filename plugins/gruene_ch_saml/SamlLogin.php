<?php

namespace app\plugins\gruene_ch_saml;

use app\components\{LoginProviderInterface, RequestContext};
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use SimpleSAML\Auth\Simple;

class SamlLogin implements LoginProviderInterface
{
    private const PARAM_EMAIL = 'email';
    private const PARAM_USERNAME = 'username';
    private const PARAM_GIVEN_NAME = 'first_name';
    private const PARAM_FAMILY_NAME = 'last_name';

    public function getId(): string
    {
        return Module::LOGIN_KEY;
    }

    public function getName(): string
    {
        return 'Grüne / Les Vert-E-S';
    }

    public function renderLoginForm(string $backUrl, bool $active): string
    {
        if (!$active) {
            return '';
        }
        return \Yii::$app->controller->renderPartial('@app/plugins/gruene_ch_saml/views/login', [
            'backUrl' => $backUrl
        ]);
    }

    /**
     * @throws \Exception
     */
    private function getOrCreateUser(array $params): User
    {
        $email = $params[self::PARAM_EMAIL][0];
        $givenname = (isset($params[self::PARAM_GIVEN_NAME]) ? $params[self::PARAM_GIVEN_NAME][0] : '');
        $familyname = (isset($params[self::PARAM_FAMILY_NAME]) ? $params[self::PARAM_FAMILY_NAME][0] : '');
        $username = $params[self::PARAM_USERNAME][0];
        $auth = Module::AUTH_KEY_USERS . ':' . $username;

        /** @var User|null $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $user = new User();
        }

        $user->name = $givenname . ' ' . $familyname;
        $user->nameGiven = $givenname;
        $user->nameFamily = $familyname;
        $user->email = $email;
        $user->emailConfirmed = 1;
        $user->fixedData = User::FIXED_NAME;
        $user->auth = $auth;
        $user->status = User::STATUS_CONFIRMED;
        $user->organization = '';
        if (!$user->save()) {
            throw new \Exception('Could not create user');
        }

        return $user;
    }

    public function performLoginAndReturnUser(): User
    {
        $samlClient = new Simple('gruene-ch');

        $samlClient->requireAuth([]);
        if (!$samlClient->isAuthenticated()) {
            throw new \Exception('SimpleSaml: Something went wrong on requireAuth');
        }
        $params = $samlClient->getAttributes();

        $user = $this->getOrCreateUser($params);
        RequestContext::getYiiUser()->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

        $user->dateLastLogin = date('Y-m-d H:i:s');
        $user->save();

        return $user;
    }

    public function userWasLoggedInWithProvider(?User $user): bool
    {
        if (!$user || !$user->auth) {
            return false;
        }
        $authParts = explode(':', $user->auth);

        return $authParts[0] === Module::AUTH_KEY_USERS;
    }

    public function usernameToAuth(string $username): string
    {
        return Module::AUTH_KEY_USERS . ':' . $username;
    }

    public function getSelectableUserOrganizations(User $user): ?array
    {
        return null;
    }


    public function logoutCurrentUserIfRelevant(string $backUrl): ?string
    {
        $user = User::getCurrentUser();
        if (!$this->userWasLoggedInWithProvider($user)) {
            return null;
        }

        $samlClient = new Simple('gruene-ch');
        if ($samlClient->isAuthenticated()) {
            $samlClient->logout();
        }

        return $backUrl;
    }

    public function renderAddMultipleUsersForm(): ?string
    {
        return null;
    }
}
