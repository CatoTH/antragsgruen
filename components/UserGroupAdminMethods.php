<?php

namespace app\components;

use app\components\mail\Tools as MailTools;
use app\models\exceptions\{AlreadyExists, MailNotSent, UserEditFailed};
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, ConsultationUserGroup, EMailLog, User};
use yii\web\{Request, Session};

class UserGroupAdminMethods
{
    /** @var Consultation */
    private $consultation;

    /** @var Request */
    private $request;

    /** @var Session */
    private $session;

    public function setRequestData(Consultation $consultation, Request $request, Session $session): void
    {
        $this->consultation = $consultation;
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * Hint: later it will be possible to select a group when inviting the user. Until then, it's a hard-coded group.
     */
    public function getDefaultUserGroup(): ?ConsultationUserGroup
    {
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->templateId === ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                return $userGroup;
            }
        }
        return null;
    }

    /**
     * Someone with only consultation-level privileges may not grant site-level privileges
     *
     * @throws UserEditFailed
     */
    private function preventInvalidSiteAdminEdit(Consultation $consultation, ConsultationUserGroup $group): void
    {
        if ($consultation->havePrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            // This check is not relevant if the user is Site Admin
            return;
        }

        if ($group->consultationId === null) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_siteprivesc'));
        }
    }

    /**
     * @throws UserEditFailed
     */
    private function preventRemovingMyself(Consultation $consultation, ConsultationUserGroup $group, User $user): void
    {
        $myself = User::getCurrentUser();
        if ($myself->havePrivilege($consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            // You cannot unassign yourself from a siteAdmin-role if you are site-admin.
            // But everyone else and yourself from any other role
            if ($group->containsPrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN) && $user->id === $myself->id) {
                throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_lockout'));
            } else {
                return;
            }
        }

        // Now we assume, the user is a regular consultation-level admin.
        // They can remove other users from admin roles, or themselves from non-admin roles
        if ($group->containsPrivilege(ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS) && $user->id === $myself->id) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_lockout'));
        }
    }

    /**
     * @throws UserEditFailed
     */
    public function setUserGroupsToUser(int $userId, array $groupIds): void
    {
        $user = User::findOne(['id' => $userId]);
        $userHasGroups = [];

        // Remove all groups belonging to this consultation that are not in the array sent by the client
        foreach ($user->userGroups as $userGroup) {
            $userHasGroups[] = $userGroup->id;

            if (!$userGroup->isRelevantForConsultation($this->consultation)) {
                continue;
            }
            if (!in_array($userGroup->id, $groupIds)) {
                $this->preventInvalidSiteAdminEdit($this->consultation, $userGroup);
                $this->preventRemovingMyself($this->consultation, $userGroup, $user);
                /** @noinspection PhpUnhandledExceptionInspection */
                $user->unlink('userGroups', $userGroup, true);
            }
        }

        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if (in_array($userGroup->id, $groupIds) && !in_array($userGroup->id, $userHasGroups)) {
                $this->preventInvalidSiteAdminEdit($this->consultation, $userGroup);
                $user->link('userGroups', $userGroup);
            }
        }

        $this->consultation->refresh();
    }

    private function getUserGroup(int $userGroupId): ?ConsultationUserGroup
    {
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->id === $userGroupId) {
                return $userGroup;
            }
        }
        return null;
    }

    public function setUserGroupUsers(int $groupId, array $userIds): void
    {
        $userGroup = $this->getUserGroup($groupId);
        if (!$userGroup) {
            return;
        }

        // Remove all users that should not be in the group anymore
        $existingUserIds = [];
        $defaultGroup = $this->getDefaultUserGroup();
        foreach ($userGroup->users as $user) {
            if (!in_array($user->id, $userIds)) {
                $user->unlink('userGroups', $userGroup, true);
                if (count($user->getUserGroupsForConsultation($this->consultation)) === 0) {
                    $user->link('userGroups', $defaultGroup);
                }
            } else {
                $existingUserIds[] = $user->id;
            }
        }

        foreach ($userIds as $userId) {
            if (in_array($userId, $existingUserIds)) {
                continue;
            }
            $user = User::findOne(['id' => $userId]);
            $user->link('userGroups', $userGroup);
        }

        $userGroup->refresh();
        $this->consultation->refresh();
    }

    /**
     * @throws UserEditFailed
     */
    public function removeUser(int $userId): void
    {
        $myself = User::getCurrentUser();
        if ($userId === $myself->id) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_lockout'));
        }

        $user = User::findOne(['id' => $userId]);
        if ($user->hasPrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN) &&
            !$myself->hasPrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_siteprivesc'));
        }

        foreach ($user->getUserGroupsForConsultation($this->consultation) as $userGroup) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $user->unlink('userGroups', $userGroup, true);
        }

        $this->consultation->refresh();
    }

    public function createUserGroup(string $groupName): void
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $this->consultation->siteId;
        $group->consultationId = $this->consultation->id;
        $group->title = $groupName;
        $group->externalId = null;
        $group->templateId = null;
        $group->permissions = '';
        $group->selectable = 1;
        $group->save();
    }

    /**
     * @throws UserEditFailed
     */
    public function removeUserGroup(int $groupId): void
    {
        $group = ConsultationUserGroup::findOne(['id' => $groupId]);
        if (!$group) {
            throw new UserEditFailed('Group does not exist');
        }
        if (!$group->isUserDeletable()) {
            throw new UserEditFailed('Group cannot be deleted');
        }

        $defaultGroup = $this->getDefaultUserGroup();
        foreach ($group->users as $user) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $user->unlink('userGroups', $group, true);
            if (count($user->getUserGroupsForConsultation($this->consultation)) === 0) {
                $user->link('userGroups', $defaultGroup);
            }
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $group->delete();
    }

    /**
     * @throws AlreadyExists
     */
    private function addUserBySamlWw(string $username, ConsultationUserGroup $initGroup): User
    {
        $auth = 'openid:https://service.gruene.de/openid/' . $username;

        /** @var User|null $user */
        $user = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if ($user) {
            // If the user already exist AND is already in the group, we will abort
            foreach ($user->userGroups as $userGroup) {
                if ($userGroup->id === $initGroup->id) {
                    throw new AlreadyExists();
                }
            }
        } else {
            $user = new User();
            $user->auth = $auth;
            $user->email = '';
            $user->name = '';
            $user->emailConfirmed = 0;
            $user->pwdEnc = null;
            $user->status = User::STATUS_CONFIRMED;
            $user->organizationIds = '';
            $user->save();
        }

        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->id === $initGroup->id) {
                $user->link('userGroups', $userGroup);
            }
        }

        return $user;
    }

    public function addUsersBySamlWw(): void
    {
        $usernames = explode("\n", $this->request->post('samlWW', ''));

        $errors = [];
        $alreadyExisted = [];
        $created = 0;

        for ($i = 0; $i < count($usernames); $i++) {
            if (trim($usernames[$i]) === '') {
                continue;
            }
            try {
                $initGroup = $this->getDefaultUserGroup();
                $this->addUserBySamlWw($usernames[$i], $initGroup);
                $created++;
            } catch (AlreadyExists $e) {
                $alreadyExisted[] = $usernames[$i];
            } catch (\Exception $e) {
                $errors[] = $usernames[$i] . ': ' . $e->getMessage();
            }
        }
        if ($created === 0) {
            $errors[] = \Yii::t('admin', 'siteacc_user_added_0');
        }
        if (count($errors) > 0) {
            $errMsg = \Yii::t('admin', 'siteacc_err_occ') . ': ' . implode("\n", $errors);
            $this->session->setFlash('error', $errMsg);
        }
        if (count($alreadyExisted) > 0) {
            $this->session->setFlash('info', \Yii::t('admin', 'siteacc_user_had') . ': ' . implode(', ', $alreadyExisted));
        }
        if ($created > 0) {
            if ($created === 1) {
                $msg = str_replace('%NUM%', (string)$created, \Yii::t('admin', 'siteacc_user_added_x'));
            } else {
                $msg = str_replace('%NUM%', (string)$created, \Yii::t('admin', 'siteacc_user_added_x'));
            }
            $this->session->setFlash('success', $msg);
        }
    }

    private function sendWelcomeEmail(User $user, ?string $emailText, ?string $plainPassword): void
    {
        if ($emailText === null || trim($emailText) === '') {
            return;
        }

        $consUrl = UrlHelper::absolutizeLink(UrlHelper::homeUrl());
        $emailText = str_replace('%LINK%', $consUrl, $emailText);

        if ($plainPassword && $user->isEmailAuthUser()) {
            $accountText = str_replace(
                ['%EMAIL%', '%PASSWORD%'],
                [$user->email, $plainPassword],
                \Yii::t('user', 'acc_grant_email_userdata')
            );
        } else {
            $accountText = '';
        }

        try {
            MailTools::sendWithLog(
                EMailLog::TYPE_ACCESS_GRANTED,
                $this->consultation,
                $user->email,
                $user->id,
                \Yii::t('user', 'acc_grant_email_title'),
                $emailText,
                '',
                ['%ACCOUNT%' => $accountText]
            );
        } catch (MailNotSent $e) {
            $this->session->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
        }
    }

    /**
     * @throws AlreadyExists
     */
    public function addUserByEmail(string $email, string $name, ?string $setPassword, ConsultationUserGroup $initGroup, string $emailText): User
    {
        $email = mb_strtolower($email);
        $auth = 'email:' . $email;

        /** @var User|null $user */
        $user = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if ($user) {
            // If the user already exist AND is already in the group, we will abort
            foreach ($user->userGroups as $userGroup) {
                if ($userGroup->id === $initGroup->id) {
                    throw new AlreadyExists();
                }
            }
            $plainPassword = null;
        } else {
            if ($setPassword) {
                $plainPassword = $setPassword;
            } else {
                $plainPassword = User::createPassword();
            }

            $user = new User();
            $user->auth = $auth;
            $user->email = $email;
            $user->name = $name;
            $user->pwdEnc = (string)password_hash($plainPassword, PASSWORD_DEFAULT);
            $user->status = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
            $user->organizationIds = '';
            $user->save();
        }

        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->id === $initGroup->id) {
                $user->link('userGroups', $userGroup);
            }
        }

        $this->sendWelcomeEmail($user, $emailText, $plainPassword);

        return $user;
    }

    /**
     * @param ConsultationUserGroup[] $userGroups
     */
    public function createSingleDetailedUser(
        string $authType,
        string $authUsername,
        ?string $password,
        string $nameGiven,
        string $nameFamily,
        string $organization,
        array $userGroups,
        ?string $emailText
    ): User
    {
        if (!$password) {
            $password = User::createPassword();
        }

        if ($authType === 'gruenesnetz') {
            $auth = User::gruenesNetzId2Auth($authUsername);
            $email = null;
        } else {
            $auth = 'email:' . $authUsername;
            $email = $authUsername;
        }

        $user = new User();
        $user->auth = $auth;
        $user->email = $email;
        $user->nameFamily = $nameFamily;
        $user->nameGiven = $nameGiven;
        $user->name = $nameGiven . ' ' . $nameFamily;
        $user->organization = $organization;
        $user->pwdEnc = (string)password_hash($password, PASSWORD_DEFAULT);
        $user->status = User::STATUS_CONFIRMED;
        $user->emailConfirmed = 1;
        $user->organizationIds = '';
        $user->save();

        foreach ($userGroups as $userGroup) {
            $user->link('userGroups', $userGroup);
        }
        $user->refresh();

        $this->sendWelcomeEmail($user, $emailText, $password);

        return $user;
    }

    /**
     * @param ConsultationUserGroup[] $userGroups
     */
    public function addSingleDetailedUser(User $user, array $userGroups, ?string $emailText): User
    {
        foreach ($userGroups as $userGroup) {
            $user->link('userGroups', $userGroup);
        }
        $user->refresh();

        $this->sendWelcomeEmail($user, $emailText, null);

        return $user;
    }

    public function addUsersByEmail(): void
    {
        $params   = AntragsgruenApp::getInstance();
        $post     = $this->request->post();
        $hasEmail = ($params->mailService['transport'] !== 'none');

        $emails    = explode("\n", $post['emailAddresses']);
        $names     = explode("\n", $post['names']);
        $passwords = ($hasEmail ? null : explode("\n", $post['passwords']));

        if (count($emails) !== count($names)) {
            $this->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
        } elseif (!$hasEmail && count($emails) !== count($passwords)) {
            $this->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
        } else {
            $errors         = [];
            $alreadyExisted = [];
            $created        = 0;

            for ($i = 0; $i < count($emails); $i++) {
                if ($emails[$i] === '') {
                    continue;
                }
                try {
                    $this->addUserByEmail(
                        trim($emails[$i]),
                        trim($names[$i]),
                        ($hasEmail ? null : $passwords[$i]),
                        $this->getDefaultUserGroup(),
                        ($hasEmail ? $post['emailText'] : '')
                    );
                    $created++;
                } catch (AlreadyExists $e) {
                    $alreadyExisted[] = $emails[$i];
                } catch (\Exception $e) {
                    $errors[] = $emails[$i] . ': ' . $e->getMessage();
                }
            }
            if (count($errors) > 0) {
                $errMsg = \Yii::t('admin', 'siteacc_err_occ') . ': ' . implode(', ', $errors);
                $this->session->setFlash('error', $errMsg);
            }
            if (count($alreadyExisted) > 0) {
                $this->session->setFlash('info', \Yii::t('admin', 'siteacc_user_had') . ': ' .
                    implode(', ', $alreadyExisted));
            }
            if ($created > 0) {
                if ($created === 1) {
                    $msg = str_replace('%NUM%', (string)$created, \Yii::t('admin', 'siteacc_user_added_x'));
                } else {
                    $msg = str_replace('%NUM%', (string)$created, \Yii::t('admin', 'siteacc_user_added_x'));
                }
                $this->session->setFlash('success', $msg);
            } else {
                $this->session->setFlash('error', \Yii::t('admin', 'siteacc_user_added_0'));
            }
        }
    }
}
