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
    private const GROUP_NAME_LV_VORSTAND = 'LV Vorstand';
    private const GROUP_NAME_AL_RECHT = 'AL Recht';
    private const GROUP_NAME_V1_REFERAT = 'Referat %NAME% (V1)';
    private const GROUP_NAME_V2_BUEROLEITUNG = 'Antrags-Veröffentlichung';
    private const GROUP_NAME_V2_AUSSCHUSS = 'Ausschuss %NAME% (V2)';
    private const GROUP_NAME_V3_REDAKTION = 'Redaktionsausschuss';
    private const GROUP_NAME_V4_KOORDINIERUNG = 'Koordinierungsausschuss';
    private const GROUP_NAME_V5_ARBEITSGRUPPE = 'Arbeitsgruppe %NAME% (V5)';
    private const GROUP_NAME_V5_ARBEITSGRUPPE_LEITUNG = 'Arbeitsgruppe Leitung (V5)';
    private const GROUP_NAME_V6_BUEROLEITUNG = 'Antrags-Veröffentlichung';
    private const GROUP_NAME_V6_REDAKTION = 'Redaktionsausschuss';
    private const GROUP_NAME_V7_BESCHLUSSFASSUNG = 'Veröffentlichung Beschlüsse (V7)';

    /** @var array{array{title: string, motionPrefix: string|null, position: int, themengebiete: array{array{title: string, position: int}}}}  */
    private const AGENDA_ITEMS_SACHGEBIETE = [
        [
            'title' => 'Sachgebiet I - Sicherheits- und Verteidigungspolitik; Einsätze und Missionen; Europa',
            'motionPrefix' => 'I/',
            'position' => 1,
            'themengebiete' => [
                [
                    'title' => 'Thema 1',
                    'position' => 1,
                ],
                [
                    'title' => 'Thema 2',
                    'position' => 2,
                ],
            ]
        ],
        [
            'title' => 'Sachgebiet II',
            'motionPrefix' => 'II/',
            'position' => 2,
            'themengebiete' => [],
        ],
        [
            'title' => 'Sachgebiet III - Dienst- und Laufbahnrecht',
            'motionPrefix' => 'III/',
            'position' => 3,
            'themengebiete' => [],
        ],
        [
            'title' => 'Sachgebiet IV',
            'motionPrefix' => 'IV/',
            'position' => 4,
            'themengebiete' => [],
        ],
        [
            'title' => 'Sachgebiet V',
            'motionPrefix' => 'V/',
            'position' => 5,
            'themengebiete' => [],
        ],
        [
            'title' => 'Sachgebiet VI',
            'motionPrefix' => 'VI/',
            'position' => 6,
            'themengebiete' => [],
        ],
    ];

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

        $this->createGlobalUserGroups($consultation);

        if ($consultation->urlPath === Module::CONSULTATION_URL_BUND) {
            $this->createBundUserGroups($consultation);
        } else {
            $this->createLvUserGroups($consultation);
        }

        echo "Created the necessary user groups.\n";
    }

    private function createGlobalUserGroups(Consultation $consultation): void
    {
        $alRecht = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_AL_RECHT);
        $alRechtPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Module::PRIVILEGE_DBWV_ASSIGN_TOPIC . ']}]}';
        $alRecht->setGroupPermissions(UserGroupPermissions::fromDatabaseString($alRechtPrivileges, false));
        $alRecht->save();

        $koordinierung = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V4_KOORDINIERUNG);
        $koordinierungPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Module::PRIVILEGE_DBWV_V4_MOVE_TO_MAIN . ']}]}';
        $koordinierung->setGroupPermissions(UserGroupPermissions::fromDatabaseString($koordinierungPrivileges, false));
        $koordinierung->save();
    }

    private function createLvUserGroups(Consultation $consultation): void
    {
        ConsultationUserGroup::getOrCreateUserGroup($consultation, Module::GROUP_NAME_ANTRAGSBERECHTIGT);
        ConsultationUserGroup::getOrCreateUserGroup($consultation, Module::GROUP_NAME_DELEGIERTE);

        $lvVorstand = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_LV_VORSTAND);
        $lvVorstandPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_MOTION_SEE_UNPUBLISHED . ']}]}';
        $lvVorstand->setGroupPermissions(UserGroupPermissions::fromDatabaseString($lvVorstandPrivileges, false));
        $lvVorstand->save();

        $lvBueroleitung = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V2_BUEROLEITUNG);
        $lvBueroleitungPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_SCREENING . ']}]}';
        $lvBueroleitung->setGroupPermissions(UserGroupPermissions::fromDatabaseString($lvBueroleitungPrivileges, false));
        $lvBueroleitung->save();

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V1_REFERAT);
            $groupName = str_replace('/', ' ', $groupName);
            $tag = $this->findExistingMainTag($consultation, $item['title']);
            $group = ConsultationUserGroup::getOrCreateUserGroup($consultation, $groupName);
            $groupPrivileges = '{"privileges":[
                {"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Module::PRIVILEGE_DBWV_V1_EDITORIAL . ']},
                {"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Module::PRIVILEGE_DBWV_ASSIGN_TOPIC . ']}
            ]}';
            $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
            $group->save();
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V2_AUSSCHUSS);
            $groupName = str_replace('/', ' ', $groupName);
            $tag = $this->findExistingMainTag($consultation, $item['title']);
            $group = ConsultationUserGroup::getOrCreateUserGroup($consultation, $groupName);
            $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Privileges::PRIVILEGE_CHANGE_PROPOSALS . ']}]}';
            $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
            $group->save();
        }

        $redaktion = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V3_REDAKTION);
        $redaktionPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_MOTION_STATUS_EDIT . ']}]}';
        $redaktion->setGroupPermissions(UserGroupPermissions::fromDatabaseString($redaktionPrivileges, false));
        $redaktion->save();
    }

    private function createBundUserGroups(Consultation $consultation): void
    {
        ConsultationUserGroup::getOrCreateUserGroup($consultation, Module::GROUP_NAME_DELEGIERTE);

        $hvBueroleitung = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V6_BUEROLEITUNG);
        $lvBueroleitungPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_SCREENING . ']}]}';
        $hvBueroleitung->setGroupPermissions(UserGroupPermissions::fromDatabaseString($lvBueroleitungPrivileges, false));
        $hvBueroleitung->save();

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V5_ARBEITSGRUPPE);
            $groupName = str_replace('/', ' ', $groupName);
            $tag = $this->findExistingMainTag($consultation, $item['title']);
            $group = ConsultationUserGroup::getOrCreateUserGroup($consultation, $groupName);
            $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":' .  $tag->id . ',"privileges":[' . Privileges::PRIVILEGE_CHANGE_PROPOSALS . ']}]}';
            $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
            $group->save();
        }

        $group = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V5_ARBEITSGRUPPE_LEITUNG);
        $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_CHANGE_PROPOSALS . ']}]}';
        $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
        $group->save();

        $redaktion = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V6_REDAKTION);
        $redaktionPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Privileges::PRIVILEGE_MOTION_STATUS_EDIT . ']}]}';
        $redaktion->setGroupPermissions(UserGroupPermissions::fromDatabaseString($redaktionPrivileges, false));
        $redaktion->save();

        $group = ConsultationUserGroup::getOrCreateUserGroup($consultation, self::GROUP_NAME_V7_BESCHLUSSFASSUNG);
        $groupPrivileges = '{"privileges":[{"motionTypeId":null,"agendaItemId":null,"tagId":null,"privileges":[' . Module::PRIVILEGE_DBWV_V7_PUBLISH_RESOLUTION . ']}]}';
        $group->setGroupPermissions(UserGroupPermissions::fromDatabaseString($groupPrivileges, false));
        $group->save();
    }

    private function findExistingMainTag(Consultation $consultation, string $title): ?ConsultationSettingsTag
    {
        return ConsultationSettingsTag::findOne([
            'title' => $title,
            'type' => ConsultationSettingsTag::TYPE_PUBLIC_TOPIC,
            'parentTagId' => null,
            'consultationId' => $consultation->id
        ]);
    }

    private function createOrGetMainTag(Consultation $consultation, int $position, string $title, ?string $motionPrefix): ConsultationSettingsTag
    {
        $tag = $this->findExistingMainTag($consultation, $title);
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
        $tag = ConsultationSettingsTag::findOne([
            'consultationId' => $consultation->id,
            'title' => $title,
            'type' => ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE,
            'parentTagId' => $parentTag->id,
        ]);
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

        $this->createGlobalUserAccounts($consultation);

        if ($consultation->urlPath === Module::CONSULTATION_URL_BUND) {
            $this->createBundUserAccounts($consultation);
        } else {
            $this->createLvUserAccounts($consultation);
        }


        echo "Created the dummy accounts.\n";
    }

    private function addUserGroup(User $user, ConsultationUserGroup $group): void
    {
        foreach ($group->users as $u) {
            if ($u->id === $user->id) {
                return;
            }
        }
        $group->addUser($user);
    }

    private function createGlobalUserAccounts(Consultation $consultation): void
    {
        $alRechtGroup = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_AL_RECHT]);
        if (!$alRechtGroup) {
            echo "AL Recht Group not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount('al-recht@example.org', 'Test', 'AL', 'Recht', 'DBwV');
        $this->addUserGroup($user, $alRechtGroup);


        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V4_KOORDINIERUNG]);
        if (!$group) {
            echo "Group " . self::GROUP_NAME_V4_KOORDINIERUNG . " not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount('koordinierungsausschuss@example.org', 'Test', 'Koordinierungs', 'Ausschuss', 'DBwV');
        if (count($user->userGroups) === 0) {
            $group->addUser($user);
        }
        $this->addUserGroup($user, $group);
    }

    private function createLvUserAccounts(Consultation $consultation): void
    {
        $urlPath = $consultation->urlPath;
        $this->createTestAccountsForGroup($consultation, 'Antragsberechtigte', $urlPath.'-antragsberechtigt', 'Organisation', 10);
        $this->createTestAccountsForGroup($consultation, 'Delegierte', $urlPath.'-delegiert', 'Organisation', 50);

        $lvVorstandGroup = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_LV_VORSTAND]);
        if (!$lvVorstandGroup) {
            echo "AL Recht Group not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-vorstand@example.org', 'Test', 'LV', 'Vorstand', 'LV Süd');
        if (count($user->userGroups) === 0) {
            $lvVorstandGroup->addUser($user);
        }

        $lvBueroleitungGroup = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V2_BUEROLEITUNG]);
        if (!$lvBueroleitungGroup) {
            echo "Büroleitung Group not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-bueroleitung@example.org', 'Test', 'LV', 'Büroleitung', 'LV Süd');
        if (count($user->userGroups) === 0) {
            $lvBueroleitungGroup->addUser($user);
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V1_REFERAT);
            $groupName = str_replace('/', ' ', $groupName);
            $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
            if (!$group) {
                echo "Group $groupName not found\n";
                return;
            }
            $prefix = explode("/", $item['motionPrefix'])[0];
            $user = $this->createOrGetUserAccount($urlPath.'-referat-' . $prefix . '@example.org', 'Test', 'Referat', $item['title'], 'DBwV');
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V2_AUSSCHUSS);
            $groupName = str_replace('/', ' ', $groupName);
            $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
            if (!$group) {
                echo "Group $groupName not found\n";
                return;
            }
            $prefix = explode("/", $item['motionPrefix'])[0];
            $user = $this->createOrGetUserAccount($urlPath.'-ausschuss-' . $prefix . '@example.org', 'Test', 'Ausschuss', $item['title'], 'DBwV');
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }

        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V3_REDAKTION]);
        if (!$group) {
            echo "Group " . self::GROUP_NAME_V3_REDAKTION . " not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-redaktion@example.org', 'Test', 'Redaktions', 'Ausschuss', 'DBwV');
        if (count($user->userGroups) === 0) {
            $group->addUser($user);
        }
    }

    private function createBundUserAccounts(Consultation $consultation): void
    {
        $urlPath = $consultation->urlPath;
        $this->createTestAccountsForGroup($consultation, 'Delegierte', $urlPath.'-delegiert', 'Organisation', 50);

        $hvBueroleitungGroup = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V6_BUEROLEITUNG]);
        if (!$hvBueroleitungGroup) {
            echo "Büroleitung Group not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-bueroleitung@example.org', 'Test', 'HV', 'Büroleitung', 'HV');
        if (count($user->userGroups) === 0) {
            $hvBueroleitungGroup->addUser($user);
        }

        foreach (self::AGENDA_ITEMS_SACHGEBIETE as $item) {
            $groupName = str_replace('%NAME%', $item['motionPrefix'], self::GROUP_NAME_V5_ARBEITSGRUPPE);
            $groupName = str_replace('/', ' ', $groupName);
            $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => $groupName]);
            if (!$group) {
                echo "Group $groupName not found\n";
                return;
            }
            $userName = explode(" - ", $item['title'])[0];
            $prefix = explode("/", $item['motionPrefix'])[0];
            $user = $this->createOrGetUserAccount($urlPath.'-arbeitsgruppe-' . $prefix . '@example.org', 'Test', 'Arbeitsgruppe', $userName, 'DBwV');
            if (count($user->userGroups) === 0) {
                $group->addUser($user);
            }
        }

        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V5_ARBEITSGRUPPE_LEITUNG]);
        if (!$group) {
            echo "Group " . self::GROUP_NAME_V5_ARBEITSGRUPPE . " not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-arbeitsgruppe-leitung@example.org', 'Test', 'Arbeitsgruppe', 'Leitung', 'DBwV');
        if (count($user->userGroups) === 0) {
            $group->addUser($user);
        }

        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V6_REDAKTION]);
        if (!$group) {
            echo "Group " . self::GROUP_NAME_V6_REDAKTION . " not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-redaktion@example.org', 'Test', 'Redaktions', 'Ausschuss', 'DBwV');
        if (count($user->userGroups) === 0) {
            $group->addUser($user);
        }

        $group = ConsultationUserGroup::findOne(['consultationId' => $consultation->id, 'title' => self::GROUP_NAME_V7_BESCHLUSSFASSUNG]);
        if (!$group) {
            echo "Group " . self::GROUP_NAME_V7_BESCHLUSSFASSUNG . " not found\n";
            return;
        }
        $user = $this->createOrGetUserAccount($urlPath.'-beschlussfassung@example.org', 'Test', 'Beschlüsse', 'Veröffentlichen', 'DBwV');
        if (count($user->userGroups) === 0) {
            $group->addUser($user);
        }
    }
}
