<?php

declare(strict_types=1);

namespace app\plugins\dbwv\commands;

use app\models\settings\Privileges;
use app\models\settings\Tag;
use app\models\settings\UserGroupPermissions;
use app\plugins\dbwv\Module;
use app\models\db\{Consultation, ConsultationSettingsTag, ConsultationUserGroup, User};
use yii\console\Controller;

class SetupController extends Controller
{
    private const GROUP_NAME_AL_RECHT = 'AL Recht';
    private const GROUP_NAME_V1_REFERAT = 'Referat %NAME% (V1)';
    private const GROUP_NAME_V2_ARBEITSGRUPPE = 'Arbeitsgruppe %NAME% (V2)';

    /** @var array{array{title: string, motionPrefix: string|null, position: int, themengebiete: array{array{title: string, position: int}}}}  */
    private const AGENDA_ITEMS_SACHGEBIETE = [
        [
            'title' => 'Satzung',
            'motionPrefix' => 'S',
            'position' => 1,
            'themengebiete' => [
                [
                    'title' => '§1 Zweck',
                    'position' => 1,
                ],
                [
                    'title' => '§2 Vorstand',
                    'position' => 2,
                ],
            ]
        ],
        [
            'title' => 'Umwelt',
            'motionPrefix' => 'U',
            'position' => 2,
            'themengebiete' => [],
        ],
        [
            'title' => 'Sonstiges',
            'motionPrefix' => null,
            'position' => 3,
            'themengebiete' => [],
        ],
    ];

    private function createUserGroupIfNotExists(Consultation $consultation, string $title): ConsultationUserGroup
    {
        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $title]);
        if (!$group) {
            $group = new ConsultationUserGroup();
            $group->consultationId = $consultation->id;
            $group->siteId = $consultation->siteId;
            $group->title = $title;
            $group->position = 0;
            $group->selectable = 1;
            $group->save();
        }

        return $group;
    }

    /**
     * Create necessary groups for a consultation. To be called after Tag creation.
     */
    public function actionUserGroups(string $urlPath): void
    {
        $consultation = Consultation::findOne(['urlPath' => $urlPath]);
        if (!$consultation) {
            echo "Consultation not found\n";
            return;
        }

        $this->createUserGroupIfNotExists($consultation, 'Antragsberechtigte');
        $this->createUserGroupIfNotExists($consultation, 'Delegierte');

        $alRecht = $this->createUserGroupIfNotExists($consultation, self::GROUP_NAME_AL_RECHT);
        $alRechtPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Module::PRIVILEGE_DBWV_V1_ASSIGN_TOPIC . ']}]}';
        $alRecht->setGroupPermissions(UserGroupPermissions::fromDatabaseString($alRechtPrivileges, false));
        $alRecht->save();

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['title'], self::GROUP_NAME_V1_REFERAT);
            $tag = ConsultationSettingsTag::findOne(['title' => $item['title'], 'type' => ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, 'parentTagId' => null]);
            $group = $this->createUserGroupIfNotExists($consultation, $groupName);
            $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Module::PRIVILEGE_DBWV_V1_EDITORIAL . ']}]}';
            $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
            $group->save();
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['title'], self::GROUP_NAME_V2_ARBEITSGRUPPE);
            $tag = ConsultationSettingsTag::findOne(['title' => $item['title'], 'type' => ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, 'parentTagId' => null]);
            $group = $this->createUserGroupIfNotExists($consultation, $groupName);
            $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Privileges::PRIVILEGE_CHANGE_PROPOSALS . ']}]}';
            $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
            $group->save();
        }

        echo "Created the necessary user groups.\n";
    }

    private function createOrGetMainTag(Consultation $consultation, int $position, string $title, ?string $motionPrefix): ConsultationSettingsTag
    {
        $tag = ConsultationSettingsTag::findOne(['title' => $title, 'type' => ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, 'parentTagId' => null]);
        if ($tag) {
            if ($tag->position !== $position) {
                $tag->position = $position;
                $tag->save();
            }
            return $tag;
        }

        $settings = new Tag(null);
        $settings->motionPrefix = $motionPrefix;

        $tag = new ConsultationSettingsTag();
        $tag->consultationId = $consultation->id;
        $tag->type = ConsultationSettingsTag::TYPE_PUBLIC_TOPIC;
        $tag->title = $title;
        $tag->position = $position;
        $tag->setSettingsObj($settings);
        $tag->save();

        return $tag;
    }

    private function createOrGetSecondaryTag(Consultation $consultation, ConsultationSettingsTag $parentTag, int $position, string $title): ConsultationSettingsTag
    {
        $tag = ConsultationSettingsTag::findOne(['title' => $title, 'type' => ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, 'parentTagId' => $parentTag->id]);
        if ($tag) {
            if ($tag->position !== $position) {
                $tag->position = $position;
                $tag->save();
            }
            return $tag;
        }

        $tag = new ConsultationSettingsTag();
        $tag->consultationId = $consultation->id;
        $tag->parentTagId = $parentTag->id;
        $tag->type = ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE;
        $tag->title = $title;
        $tag->position = $position;
        $tag->save();

        return $tag;
    }

    /**
     * Create necessary tags / "Sachgebiete" and "Themenbereiche" for a consultation. Call before user groups.
     */
    public function actionTags(string $urlPath): void
    {
        $consultation = Consultation::findOne(['urlPath' => $urlPath]);
        if (!$consultation) {
            echo "Consultation not found\n";
            return;
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $mainTag = $this->createOrGetMainTag($consultation, $item['position'], $item['title'], $item['motionPrefix']);

            foreach ($item['themengebiete'] as $secondaryTag) {
                $this->createOrGetSecondaryTag($consultation, $mainTag, $secondaryTag['position'], $secondaryTag['title']);
            }
        }

        echo "Created the necessary tags.\n";
    }

    private function createOrGetUserAccount(string $email, string $password, string $givenName, string $familyName, string $organization): User
    {
        $user = User::findOne(['auth' => 'email:' . $email]);
        if ($user) {
            return $user;
        }

        $user = new User();
        $user->name = $givenName . ' ' . $familyName;
        $user->nameFamily = $familyName;
        $user->nameGiven = $givenName;
        $user->organization = $organization;
        $user->email = $email;
        $user->emailConfirmed = 1;
        $user->auth = 'email:' . $email;
        $user->dateCreation = date('Y-m-d H:i:s');
        $user->fixedData = User::FIXED_NAME | User::FIXED_ORGA;
        $user->status = User::STATUS_CONFIRMED;
        $user->pwdEnc = (string)password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }

    private function createTestAccountsForGroup(Consultation $consultation, string $groupName, string $emailPrefix, string $orgaPrefix, int $count): void
    {
        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
        if (!$group) {
            throw new \Exception('Group not found: ' . $groupName);
        }
        for ($i = 0; $i < $count; $i++) {
            $user = $this->createOrGetUserAccount(
                $emailPrefix . '-' . $i . '@example.org',
                'Test',
                'Test',
                $groupName . ' ' . $i,
                $orgaPrefix . '-' . $i
            );
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }
    }

    /**
     * Create necessary groups for a consultation
     */
    public function actionDemoAccounts(string $urlPath): void
    {
        $consultation = Consultation::findOne(['urlPath' => $urlPath]);
        if (!$consultation) {
            echo "Consultation not found\n";
            return;
        }

        $this->createTestAccountsForGroup($consultation, 'Antragsberechtigte', 'antragsberechtigt', 'Organisation', 10);
        $this->createTestAccountsForGroup($consultation, 'Delegierte', 'delegiert', 'Organisation', 50);

        $alRechtGroup = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_AL_RECHT]);
        if (!$alRechtGroup) {
            echo "AL Recht Group not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount('al-recht@example.org', 'Test', 'AL', 'Recht', 'DBwV');
        if (count($user->userGroups) === 0) {
            $alRechtGroup->addUser($user);
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            if (!$item['motionPrefix']) {
                continue;
            }
            $groupName = str_replace('%NAME%', $item['title'], self::GROUP_NAME_V1_REFERAT);
            $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
            if (!$group) {
                echo "Group $groupName not found\n";
                return;
            }
            $user = $this->createOrGetUserAccount('referat-' . $item['motionPrefix'] . '@example.org', 'Test', 'Referat', $item['title'], 'DBwV');
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            if (!$item['motionPrefix']) {
                continue;
            }
            $groupName = str_replace('%NAME%', $item['title'], self::GROUP_NAME_V2_ARBEITSGRUPPE);
            $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
            if (!$group) {
                echo "Group $groupName not found\n";
                return;
            }
            $user = $this->createOrGetUserAccount('arbeitsgruppe-' . $item['motionPrefix'] . '@example.org', 'Test', 'Arbeitsgruppe', $item['title'], 'DBwV');
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }

        echo "Created the dummy accounts.\n";
    }
}
