<?php

namespace app\plugins\gruene_de_saml;

use app\models\db\ConsultationUserGroup;
use app\components\{LoginProviderInterface, RequestContext, UrlHelper};
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Site as SiteSettings;
use SimpleSAML\Auth\Simple;
use yii\helpers\Url;

class SamlLogin implements LoginProviderInterface
{
    private const PARAM_EMAIL = 'gmnMail';
    private const PARAM_USERNAME = 'uid';
    private const PARAM_GIVEN_NAME = 'givenName';
    private const PARAM_FAMILY_NAME = 'sn';
    private const PARAM_ORGANIZATION = 'membershipOrganizationKey';

    public function getId(): string
    {
        return (string)SiteSettings::LOGIN_GRUENES_NETZ;
    }

    public function getName(): string
    {
        return 'Grünes Netz';
    }

    public function renderLoginForm(string $backUrl, bool $active): string
    {
        return \Yii::$app->controller->renderPartial('@app/plugins/gruene_de_saml/views/login', [
            'loginActive' => $active,
            'backUrl' => $backUrl,
        ]);
    }

    public function performLoginAndReturnUser(): User
    {
        $samlClient = new Simple('default-sp');

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

    /**
     * @throws \Exception
     */
    public function getOrCreateUser(array $params): User
    {
        $email = $params[self::PARAM_EMAIL][0];
        $givenname = (isset($params[self::PARAM_GIVEN_NAME]) ? $params[self::PARAM_GIVEN_NAME][0] : '');
        $familyname = (isset($params[self::PARAM_FAMILY_NAME]) ? $params[self::PARAM_FAMILY_NAME][0] : '');
        $username = $params[self::PARAM_USERNAME][0];
        $auth = $this->usernameToAuth($username);

        $organizations = $this->resolveAllOrgaIds($params[self::PARAM_ORGANIZATION] ?? []);

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
        $user->organization ??= '';
        if (!$user->save()) {
            throw new \Exception('Could not create user');
        }

        $this->syncUserGroups($user, $organizations);

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

    /**
     * @return ConsultationUserGroup[]|null
     */
    public function getSelectableUserOrganizations(User $user): ?array
    {
        $orgas = [];
        foreach ($user->userGroups as $userGroup) {
            if ($userGroup->externalId && str_starts_with($userGroup->externalId, Module::AUTH_KEY_GROUPS.':')) {
                $orgas[] = $userGroup;
            }
        }
        return $orgas;
    }

    public function usernameToAuth(string $username): string
    {
        return 'openid:https://service.gruene.de/openid/' . $username;
    }

    public function logoutCurrentUserIfRelevant(string $backUrl): ?string
    {
        $backSubdomain = UrlHelper::getSubdomain($backUrl);
        $currDomain    = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'];
        $currSubdomain = UrlHelper::getSubdomain($currDomain);

        if ($currSubdomain) {
            $user = User::getCurrentUser();
            if (!$this->userWasLoggedInWithProvider($user)) {
                return null;
            }

            // First step on the subdomain: logout and redirect to the main domain
            RequestContext::getYiiUser()->logout();
            $backParts = parse_url($backUrl);
            if ($backParts === false || !isset($backParts['host'])) {
                $backUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . $backUrl;
            }

            $backUrl = AntragsgruenApp::getInstance()->domainPlain . 'user/logout?backUrl=' . urlencode($backUrl);
        } elseif ($backSubdomain) {
            // Second step: we are on the main domain. Logout and redirect to the subdomain
            // Here, there might not be a user object (NULL), so we will proceed anyway
            self::logout();
        } else {
            $user = User::getCurrentUser();
            if (!$this->userWasLoggedInWithProvider($user)) {
                return null;
            }

            // No subdomain is involved, local logout on the main domain
            self::logout();
        }

        return $backUrl;
    }

    public static function logout(): void
    {
        $samlClient = new Simple('default-sp');

        if ($samlClient->isAuthenticated()) {
            $samlClient->logout();
        }
        RequestContext::getYiiUser()->logout();
    }

    public static function createGruenesNetzLoginUrl(string $route): string
    {
        $target_url = Url::toRoute($route);

        if (RequestContext::getYiiUser()->getIsGuest()) {
            return Url::toRoute(['/gruene_de_saml/login/login', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }

    private function resolveAllOrgaIds(array $orgaIds): array
    {
        $newOrgaIds = [];

        sort($orgaIds); // 1xx are regular pary, 2xx are GJ/BV - prioritize the first, as per #706

        foreach ($orgaIds as $orgaId) {
            if (strlen($orgaId) !== 8) {
                continue;
            }

            // Hint: The KV is the most important assignment, and the first entry is taken for the "organization" field in the user object
            $newOrgaIds[] = substr($orgaId, 0, 6) . '00'; // KV
            $newOrgaIds[] = $orgaId; // OV
            $newOrgaIds[] = substr($orgaId, 0, 3) . '00000'; // LV
            $newOrgaIds[] = substr($orgaId, 0, 1) . '0000000'; // BV / GJ
        }

        return array_values(array_unique($newOrgaIds));
    }

    private function syncUserGroups(User $user, array $newOrgaIds): void
    {
        $newUserGroupIds = array_map(function (string $orgaId): string {
            return Module::AUTH_KEY_GROUPS . ':' . $orgaId;
        }, $newOrgaIds);

        // If an organisation is already manually set, and this is a valid organisation, this should not change.
        // In all other cases, the first organisation of the list should be set as organisation name of the user.
        $previousOrga = $user->organization;
        $previousOrgaFound = false;

        $user->organizationIds = json_encode($newOrgaIds, JSON_THROW_ON_ERROR);
        $user->organization = '';
        for ($i = 0; $i < count($newUserGroupIds); $i++) {
            $userGroup = ConsultationUserGroup::findByExternalId($newUserGroupIds[$i]);
            if ($userGroup) {
                if ($user->organization === '') {
                    $user->organization = $userGroup->title;
                }
                if ($userGroup->title === $previousOrga) {
                    $previousOrgaFound = true;
                }
            }
        }
        if ($previousOrgaFound) {
            $user->organization = $previousOrga;
        }

        $oldUserGroupIds = [];
        foreach ($user->userGroups as $userGroup) {
            if ($userGroup->belongsToExternalAuth(Module::AUTH_KEY_GROUPS)) {
                $oldUserGroupIds[] = $userGroup->externalId;
                if (!in_array($userGroup->externalId, $newUserGroupIds)) {
                    $user->unlink('userGroups', $userGroup, true);
                }
            }
        }

        foreach ($newUserGroupIds as $userGroupId) {
            $userGroup = ConsultationUserGroup::findByExternalId($userGroupId);
            if ($userGroup && !in_array($userGroupId, $oldUserGroupIds)) {
                $user->link('userGroups', $userGroup);
            }
        }
        $user->save();
    }

    public function renderAddMultipleUsersForm(): ?string
    {
        return \Yii::$app->controller->renderPartial('@app/plugins/gruene_de_saml/views/users_add_multiple', []);
    }
}
