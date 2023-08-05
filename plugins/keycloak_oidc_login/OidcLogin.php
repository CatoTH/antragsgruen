<?php

namespace app\plugins\keycloak_oidc_login;

use Jumbojett\OpenIDConnectClient;
use app\components\{LoginProviderInterface, RequestContext};
use app\models\db\User;
use app\models\settings\AntragsgruenApp;

class OidcLogin implements LoginProviderInterface
{

    public function __construct(private string $issuerUrl, private string $clientId, private string $clientSecret){}

    // map KC Claims to User attributes
    private const PARAM_EMAIL = 'email';
    private const PARAM_USERNAME = 'preferred_username';
    private const PARAM_GIVEN_NAME = 'given_name';
    private const PARAM_FAMILY_NAME = 'family_name';

    public function getId(): string
    {
        return Module::LOGIN_KEY;
    }

    public function getName(): string
    {
        return 'Keycloak';
    }

    public function renderLoginForm(string $backUrl, bool $active): string
    {
        if (!$active){
            return '';
        }
        return \Yii::$app->controller->renderPartial('@app/plugins/keycloak_oidc_login/views/login', [
            'backUrl' => $backUrl
        ]);
    }

    /**
     * @throws \Exception
     */
    private function getOrCreateUser(array $params): User
    {
        $email = $params[self::PARAM_EMAIL];
        $givenname = $params[self::PARAM_GIVEN_NAME];
        $familyname = $params[self::PARAM_FAMILY_NAME];
        $username = $params[self::PARAM_USERNAME];
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
            throw new \Exception('Could not save / create user');
        }

        return $user;
    }

    public function performLoginAndReturnUser(): User
    {
        $oidc = new OpenIDConnectClient(
            $this->issuerUrl,
            $this->clientId,
            $this->clientSecret
        );
        $oidc->setRedirectURL('http://localhost:8080/keycloak-oidc');
        $oidc->authenticate();
        $params = (array) $oidc->requestUserInfo();

        $user = $this->getOrCreateUser($params);
        RequestContext::getUser()->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

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
        RequestContext::getUser()->logout(true);
        return "https://login.rote.tools/realms/user/protocol/openid-connect/logout";
    }

    public function renderAddMultipleUsersForm(): ?string
    {
        return null;
    }
}
