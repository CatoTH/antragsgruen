<?php

namespace app\plugins\gruene_de_saml;

use app\components\RequestContext;
use app\models\db\{ConsultationUserGroup, User};
use SimpleSAML\Auth\Simple;

class SamlClient
{
    const PARAM_EMAIL = 'gmnMail';
    const PARAM_USERNAME = 'uid';
    const PARAM_GIVEN_NAME = 'givenName';
    const PARAM_FAMILY_NAME = 'sn';
    const PARAM_ORGANIZATION = 'membershipOrganizationKey';

    private Simple $auth;
    private array $params;

    public function __construct()
    {
        $this->auth   = new Simple('default-sp');
        $this->params = $this->auth->getAttributes();
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

    /**
     * @throws \Exception
     */
    public function requireAuth(): void
    {
        $this->auth->requireAuth([]);
        if (!$this->auth->isAuthenticated()) {
            throw new \Exception('SimpleSaml: Something went wrong on requireAuth');
        }
        $this->params = $this->auth->getAttributes();
    }

    /**
     * @throws \Exception
     */
    public function getOrCreateUser(): User
    {
        $email = $this->params[static::PARAM_EMAIL][0];
        $givenname = (isset($this->params[static::PARAM_GIVEN_NAME]) ? $this->params[static::PARAM_GIVEN_NAME][0] : '');
        $familyname = (isset($this->params[static::PARAM_FAMILY_NAME]) ? $this->params[static::PARAM_FAMILY_NAME][0] : '');
        $username = $this->params[static::PARAM_USERNAME][0];
        $auth = User::gruenesNetzId2Auth($username);

        $organizations = $this->resolveAllOrgaIds($this->params[static::PARAM_ORGANIZATION] ?? []);

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

    public function logout(): void
    {
        if ($this->auth->isAuthenticated()) {
            $this->auth->logout();
        }
        RequestContext::getUser()->logout();
    }
}
