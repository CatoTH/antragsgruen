<?php

namespace app\commands;

use app\models\db\ConsultationUserGroup;
use app\models\db\User;
use yii\console\Controller;

class UserController extends Controller
{
    /** @var string */
    public $groupIds;
    /** @var string */
    public $organization;

    public function options($actionID): array
    {
        switch ($actionID) {
            case 'create':
                return ['groupIds', 'organization'];
            default:
                return [];
        }
    }

    /**
     * Resets the password for a given user
     */
    public function actionSetUserPassword(string $auth, string $password)
    {
        if (mb_strpos($auth, ':') === false) {
            if (mb_strpos($auth, '@') !== false) {
                $auth = 'email:' . $auth;
            } else {
                $auth = User::gruenesNetzId2Auth($auth);
            }
        }
        /** @var User|null $user */
        $user = User::findOne(['auth' => $auth]);
        if (!$user) {
            $this->stderr('User not found: ' . $auth . "\n");

            return;
        }

        $user->changePassword($password);
        $this->stdout('The password has been changed.' . "\n");
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
        $orgaIds = array_map('intval', explode(',', $this->groupIds));
        $toUserGroups = [];
        foreach ($orgaIds as $orgaId) {
            $group = ConsultationUserGroup::findOne(['id' => $orgaId]);
            if ($group) {
                $toUserGroups[] = $group;
            } else {
                $this->stderr('User group not found: ' . $orgaId);
                return 1;
            }
        }

        $user = new User();
        $user->auth = $auth;
        $user->email = $email;
        $user->nameGiven = $givenName;
        $user->nameFamily = $familyName;
        $user->name = $givenName . ' ' . $familyName;
        $user->emailConfirmed = 1;
        $user->pwdEnc = password_hash($password, PASSWORD_DEFAULT);
        $user->status = User::STATUS_CONFIRMED;
        $user->organizationIds = '';
        $user->organization = $this->organization;
        $user->save();

        foreach ($toUserGroups as $toUserGroup) {
            $user->link('userGroups', $toUserGroup);
        }

        return 0;
    }

    private function formatKurzname(string $name): string
    {
        // "Delmenhorst KV" => "KV Delmenhorst"
        $name = preg_replace("/^(.*) KV$/siu", "KV $1", $name);
        $name = preg_replace("/^(.*) RV$/siu", "RV $1", $name);
        $name = preg_replace("/^(.*) LV$/siu", "LV $1", $name);

        return $name;
    }

    /**
     * Imports site-wide user groups
     */
    public function actionImportGroup(string $authType, string $filename): int
    {
        $groups = json_decode((string)file_get_contents($filename), true, 512, JSON_THROW_ON_ERROR);
        foreach ($groups as $group) {
            $externalId = $authType . ':' . $group['gliederungsschluessel'];

            $internalGroup = ConsultationUserGroup::findOne(['externalId' => $externalId]);
            if (!$internalGroup) {
                $internalGroup = new ConsultationUserGroup();
                $internalGroup->siteId = null;
                $internalGroup->consultationId = null;
                $internalGroup->externalId = $externalId;
                $internalGroup->permissions = '';
                $internalGroup->selectable = 1;
            }
            $internalGroup->title = $this->formatKurzname($group['kurzname']);
            $internalGroup->templateId = null;
            $internalGroup->save();
        }

        return 0;
    }
}
