<?php

declare(strict_types=1);

namespace app\commands;

use app\components\{UrlHelper, UserGroupAdminMethods};
use app\models\settings\AntragsgruenApp;
use app\models\db\{ConsultationUserGroup, Site, User};
use yii\console\Controller;

class UserController extends Controller
{
    public ?string $groupIds = null;
    public ?string $organization = null;
    public ?string $password = null;
    public ?string $welcomeFile = null;

    public bool $forcePasswordChange = false;
    public bool $forceTwoFactor = false;
    public bool $preventPasswordChange = false;
    public bool $fixedName = false;
    public bool $fixedOrganization = false;

    public function options($actionID): array
    {
        return match ($actionID) {
            'create', 'create-or-update' => ['groupIds', 'organization', 'welcomeFile', 'forcePasswordChange', 'forceTwoFactor', 'preventPasswordChange', 'fixedName', 'fixedOrganization'],
            'update' => ['groupIds', 'organization', 'password'],
            default => [],
        };
    }

    private function findUserByAuth(string $auth): ?User
    {
        if (!str_contains($auth, ':') && str_contains($auth, '@')) {
            $auth = 'email:' . $auth;
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
     * Creates a user or updates their data if already existing
     *
     * Example:
     * ./yii user/create-or-update email:test@example.org test@example.org "Given Name" "Family Name" TestPassword --groupIds 1,2 --organization Antragsgrün --welcome-file welcome-email.txt
     *
     * "groupIds" refer to the primary IDs in "consultationUserGroup"
     * Optional flags:
     * --forcePasswordChange
     * --forceTwoFactor
     * --preventPasswordChange
     * --fixedName
     * --fixedOrganization
     */
    public function actionCreateOrUpdate(string $auth, string $email, string $givenName, string $familyName, string $password): int
    {
        /** @var User|null $user */
        $user = $this->findUserByAuth($auth);
        if ($user) {
            $this->updateUser($user, $this->organization, $password);
        } else {
            $this->actionCreate($auth, $email, $givenName, $familyName, $password);
        }

        return 0;
    }

    /**
     * Creates a user
     *
     * Example:
     * ./yii user/create email:test@example.org test@example.org "Given Name" "Family Name" TestPassword --groupIds 1,2 --organization Antragsgrün --welcome-file welcome-email.txt
     *
     * "groupIds" refer to the primary IDs in "consultationUserGroup"
     * Optional flags:
     * --forcePasswordChange
     * --forceTwoFactor
     * --preventPasswordChange
     * --fixedName
     * --fixedOrganization
     */
    public function actionCreate(string $auth, string $email, string $givenName, string $familyName, string $password): int
    {
        $welcomeTemplate = null;
        if ($this->welcomeFile) {
            if (!file_exists($this->welcomeFile)) {
                throw new \RuntimeException('welcome template not found');
            }
            $welcomeTemplate = file_get_contents($this->welcomeFile);
        }

        $site = Site::findOne(['subdomain' => AntragsgruenApp::getInstance()->siteSubdomain]);
        $consultation = $site->currentConsultation;
        UrlHelper::setCurrentSite($site);
        UrlHelper::setCurrentConsultation($consultation);

        $toUserGroups = $this->getToSetUserGroups();

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

        $user->fixedData = 0;
        if ($this->fixedName) {
            $user->fixedData |= User::FIXED_NAME;
        }
        if ($this->fixedOrganization) {
            $user->fixedData |= User::FIXED_ORGA;
        }

        $userSettings = $user->getSettingsObj();
        if ($this->forcePasswordChange) {
            $userSettings->forcePasswordChange = true;
        }
        if ($this->preventPasswordChange) {
            $userSettings->preventPasswordChange = true;
        }
        if ($this->forceTwoFactor) {
            $userSettings->enforceTwoFactorAuthentication = true;
        }
        $user->setSettingsObj($userSettings);

        $user->save();

        foreach ($toUserGroups as $toUserGroup) {
            $user->link('userGroups', $toUserGroup);
        }

        $this->stdout('Created the user: ' . $user->auth . "\n");

        if ($welcomeTemplate) {
            \Yii::$app->urlManager->setBaseUrl("/");
            \Yii::$app->language = substr($consultation->wordingBase, 0, 2);
            $methods = new UserGroupAdminMethods();
            $methods->setRequestData($consultation, null, null);
            $methods->sendWelcomeEmail($user, $welcomeTemplate, $password);
        }

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

        $this->updateUser($user, $this->organization, $this->password);

        return 0;
    }

    public function updateUser(?User $user, ?string $organization, ?string $password): void
    {
        $toUserGroups = $this->getToSetUserGroups();

        if ($organization !== null) {
            $user->organization = $organization;
            $user->save();
        }
        if ($password) {
            $user->changePassword($password);
        }

        $existingGroups = [];
        foreach ($user->userGroups as $userGroup) {
            $existingGroups[] = $userGroup->id;
        }

        foreach ($toUserGroups as $toUserGroup) {
            if (!in_array($toUserGroup->id, $existingGroups)) {
                $user->link('userGroups', $toUserGroup);
            }
        }

        $this->stdout('Updated the user: ' . $user->auth);
    }
}
