<?php

declare(strict_types=1);

namespace app\commands;

use app\components\{UrlHelper, UserGroupAdminMethods};
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, ConsultationUserGroup, Site, User};
use yii\console\Controller;

class UserController extends Controller
{
    public ?string $groupIds = null;
    public ?string $organization = null;
    public ?string $password = null;
    public ?string $welcomeFile = null;
    public ?string $consultationPath = null;

    public bool $forcePasswordChange = false;
    public bool $forceTwoFactor = false;
    public bool $preventPasswordChange = false;
    public bool $fixedName = false;
    public bool $fixedOrganization = false;

    public function options($actionID): array
    {
        return match ($actionID) {
            'create', 'create-or-update' => ['consultationPath', 'groupIds', 'organization', 'welcomeFile', 'forcePasswordChange', 'forceTwoFactor', 'preventPasswordChange', 'fixedName', 'fixedOrganization'],
            'update' => ['consultationPath', 'groupIds', 'organization', 'password', 'welcomeFile'],
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

    private function getAndSetConsultation(): Consultation
    {
        $consultation = null;
        $site = Site::findOne(['subdomain' => AntragsgruenApp::getInstance()->siteSubdomain]);
        if ($this->consultationPath) {
            foreach ($site->consultations as $siteConsultation) {
                if ($siteConsultation->urlPath === $this->consultationPath) {
                    $consultation = $siteConsultation;
                }
            }
        } else {
            $consultation = $site->currentConsultation;
        }

        if (!$consultation) {
            throw new \RuntimeException('Consultation not found');
        }
        UrlHelper::setCurrentSite($site);
        UrlHelper::setCurrentConsultation($consultation);

        return $consultation;
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

    private function sendWelcomeEmail(Consultation $consultation, User $user, string $welcomeTemplate, ?string $password): void
    {
        \Yii::$app->urlManager->setBaseUrl("/");
        \Yii::$app->language = substr($consultation->wordingBase, 0, 2);
        $methods = new UserGroupAdminMethods();
        $methods->setRequestData($consultation, null, null);
        $methods->sendWelcomeEmail($user, $welcomeTemplate, $password);
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

        $consultation = $this->getAndSetConsultation();

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
            $this->sendWelcomeEmail($consultation, $user, $welcomeTemplate, $password);
        }

        return 0;
    }

    /**
     * Updates a user
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

    private function updateUser(?User $user, ?string $organization, ?string $password): void
    {
        $welcomeTemplate = null;
        if ($this->welcomeFile) {
            if (!file_exists($this->welcomeFile)) {
                throw new \RuntimeException('welcome template not found');
            }
            $welcomeTemplate = file_get_contents($this->welcomeFile);
        }

        $consultation = $this->getAndSetConsultation();

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

        if ($welcomeTemplate) {
            $this->sendWelcomeEmail($consultation, $user, $welcomeTemplate, $password);
        }
    }
}
