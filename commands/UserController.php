<?php

namespace app\commands;

use app\models\db\ConsultationUserGroup;
use app\models\db\User;
use yii\console\Controller;

class UserController extends Controller
{
    public ?string $groupIds = null;
    public ?string $organization = null;
    public ?string $password = null;

    public function options($actionID): array
    {
        switch ($actionID) {
            case 'create':
                return ['groupIds', 'organization'];
            case 'update':
                return ['groupIds', 'organization', 'password'];
            default:
                return [];
        }
    }

    private function findUserByAuth(string $auth): ?User
    {
        if (mb_strpos($auth, ':') === false) {
            if (mb_strpos($auth, '@') !== false) {
                $auth = 'email:' . $auth;
            }
        }
        return User::findOne(['auth' => $auth]);
    }

    /**
     * @return ConsultationUserGroup[]
     */
    private function getToSetUserGroups(): array
    {
        if (!$this->groupIds) {
            return [];
        }

        $orgaIds = array_map('intval', explode(',', $this->groupIds));
        $toUserGroups = [];
        foreach ($orgaIds as $orgaId) {
            $group = ConsultationUserGroup::findOne(['id' => $orgaId]);
            if ($group) {
                $toUserGroups[] = $group;
            } else {
                throw new \Exception('User group not found: ' . $orgaId);
            }
        }

        return $toUserGroups;
    }

    /**
     * Resets the password for a given user
     */
    public function actionSetUserPassword(string $auth, string $password): int
    {
        /** @var User|null $user */
        $user = $this->findUserByAuth($auth);
        if (!$user) {
            $this->stderr('User not found: ' . $auth . "\n");

            return 1;
        }

        $user->changePassword($password);
        $this->stdout('The password has been changed.' . "\n");

        return 0;
    }

    /**
     * Creates a user
     *
     * Example:
     * ./yii user/create email:test@example.org test@example.org "Given Name" "Family Name" TestPassword --groupIds 1,2 --organization Antragsgrün
     *
     * "groupIds" refer to the primary IDs in "consultationUserGroup"
     */
    public function actionCreate(string $auth, string $email, string $givenName, string $familyName, string $password): int
    {
        $toUserGroups = $this->getToSetUserGroups();

        $user = new User();
        $user->auth = $auth;
        $user->email = $email;
        $user->nameGiven = $givenName;
        $user->nameFamily = $familyName;
        $user->name = $givenName . ' ' . $familyName;
        $user->emailConfirmed = 1;
        $user->pwdEnc = (string)password_hash($password, PASSWORD_DEFAULT);
        $user->status = User::STATUS_CONFIRMED;
        $user->organizationIds = '';
        $user->organization = $this->organization;
        $user->save();

        foreach ($toUserGroups as $toUserGroup) {
            $user->link('userGroups', $toUserGroup);
        }

        $this->stdout('Created the user');

        return 0;
    }

    /**
     * Creates a user
     *
     * Example:
     * ./yii user/update email:test@example.org --password TestPassword --groupIds 1,2 --organization Antragsgrün
     *
     * "groupIds" refer to the primary IDs in "consultationUserGroup"
     */
    public function actionUpdate(string $auth): int
    {
        /** @var User|null $user */
        $user = $this->findUserByAuth($auth);
        if (!$user) {
            $this->stderr('User not found: ' . $auth . "\n");

            return 1;
        }

        $toUserGroups = $this->getToSetUserGroups();

        if ($this->organization) {
            $user->organization = $this->organization;
            $user->save();
        }
        if ($this->password) {
            $user->changePassword($this->password);
        }

        foreach ($toUserGroups as $toUserGroup) {
            $user->link('userGroups', $toUserGroup);
        }

        $this->stdout('Updated the user');

        return 0;
    }
}
