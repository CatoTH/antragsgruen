<?php

namespace app\plugins\gruene_de_saml;

use app\models\db\ConsultationUserGroup;
use app\components\{LoginProviderInterface, RequestContext};
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
        RequestContext::getUser()->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

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
        $auth = User::gruenesNetzId2Auth($username);

        $organizations = $this->resolveAllOrgaIds($this->params[self::PARAM_ORGANIZATION] ?? []);

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

    public function userWasLoggedInWithProvider(?User $user): bool
    {
        if (!$user || !$user->auth) {
            return false;
        }
        $authParts = explode(':', $user->auth);

        return $authParts[0] === Module::AUTH_KEY_USERS;
    }

    public function logoutCurrentUserIfRelevant(): void
    {
        $user = User::getCurrentUser();
        if (!$this->userWasLoggedInWithProvider($user)) {
            return;
        }

        self::logout();
    }

    public static function logout(): void
    {
        $samlClient = new Simple('default-sp');

        if ($samlClient->isAuthenticated()) {
            $samlClient->logout();
        }
        RequestContext::getUser()->logout();
    }

    public static function createGruenesNetzLoginUrl(string $route): string
    {
        $target_url = Url::toRoute($route);

        if (RequestContext::getUser()->getIsGuest()) {
            return Url::toRoute(['/gruene_de_saml/login/login', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }

    private function resolveAllOrgaIds(array $orgaIds): array
    {
        $newOrgaIds = [];

        foreach ($orgaIds as $orgaId) {
            if (strlen($orgaId) !== 8) {
                continue;
            }

            $newOrgaIds[] = $orgaId; // BV / GJ
            $newOrgaIds[] = substr($orgaId, 0, 6) . '00'; // LV
            $newOrgaIds[] = substr($orgaId, 0, 3) . '00000'; // KV
            $newOrgaIds[] = substr($orgaId, 0, 1) . '0000000'; // OV
        }

        return array_values(array_unique($newOrgaIds));
    }

    private function syncUserGroups(User $user, array $newOrgaIds): void
    {
        $user->organizationIds = json_encode($newOrgaIds, JSON_THROW_ON_ERROR);
        $user->organization = '';

        $newUserGroupIds = array_map(function (string $orgaId): string {
            return Module::AUTH_KEY_GROUPS . ':' . $orgaId;
        }, $newOrgaIds);

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
            if ($userGroup) {
                $user->organization = $userGroup->title;
                $user->save();

                if (!in_array($userGroupId, $oldUserGroupIds)) {
                    $user->link('userGroups', $userGroup);
                }
            }
        }
        $user->save();
    }
}
