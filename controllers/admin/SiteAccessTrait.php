<?php

namespace app\controllers\admin;

use app\components\{UrlHelper, mail\Tools as MailTools};
use app\models\db\{ConsultationUserGroup, EMailLog, Site, Consultation, User};
use app\models\exceptions\{AlreadyExists, MailNotSent, UserEditFailed};
use app\models\settings\AntragsgruenApp;
use yii\base\ExitException;
use yii\web\Response;

/**
 * @property Site $site
 * @property Consultation $consultation
 * @method render(string $view, array $options = [])
 * @method isPostSet(string $name)
 * @method AntragsgruenApp getParams()
 */
trait SiteAccessTrait
{
    /**
     * @throws AlreadyExists
     */
    private function addUserBySamlWw(string $username, ConsultationUserGroup $initGroup): User
    {
        $auth = 'openid:https://service.gruene.de/openid/' . $username;

        /** @var User $user */
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

    private function addUsersBySamlWw(): void
    {
        $usernames = explode("\n", \Yii::$app->request->post('samlWW', ''));

        $errors         = [];
        $alreadyExisted = [];
        $created        = 0;

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
            \Yii::$app->session->setFlash('error', $errMsg);
        }
        if (count($alreadyExisted) > 0) {
            \Yii::$app->session->setFlash('info', \Yii::t('admin', 'siteacc_user_had') . ': ' . implode(', ', $alreadyExisted));
        }
        if ($created > 0) {
            if ($created == 1) {
                $msg = str_replace('%NUM%', $created, \Yii::t('admin', 'siteacc_user_added_x'));
            } else {
                $msg = str_replace('%NUM%', $created, \Yii::t('admin', 'siteacc_user_added_x'));
            }
            \Yii::$app->session->setFlash('success', $msg);
        }
    }

    /**
     * @throws AlreadyExists
     */
    private function addUserByEmail(string $email, string $name, ?string $setPassword, ConsultationUserGroup $initGroup, string $emailText): User
    {
        $email = mb_strtolower($email);
        $auth  = 'email:' . $email;

        /** @var User $user */
        $user = User::find()->where(['auth' => $auth])->andWhere('status != ' . User::STATUS_DELETED)->one();
        if ($user) {
            // If the user already exist AND is already in the group, we will abort
            foreach ($user->userGroups as $userGroup) {
                if ($userGroup->id === $initGroup->id) {
                    throw new AlreadyExists();
                }
            }
            $accountText = '';
        } else {
            if ($setPassword) {
                $password = $setPassword;
            } else {
                $password = User::createPassword();
            }

            $user = new User();
            $user->auth = $auth;
            $user->email = $email;
            $user->name = $name;
            $user->pwdEnc = password_hash($password, PASSWORD_DEFAULT);
            $user->status = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
            $user->organizationIds = '';
            $user->save();

            $accountText = str_replace(
                ['%EMAIL%', '%PASSWORD%'],
                [$email, $password],
                \Yii::t('user', 'acc_grant_email_userdata')
            );
        }

        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->id === $initGroup->id) {
                $user->link('userGroups', $userGroup);
            }
        }

        $consUrl   = UrlHelper::absolutizeLink(UrlHelper::homeUrl());
        $emailText = str_replace('%LINK%', $consUrl, $emailText);

        try {
            MailTools::sendWithLog(
                EMailLog::TYPE_ACCESS_GRANTED,
                $this->consultation,
                $email,
                $user->id,
                \Yii::t('user', 'acc_grant_email_title'),
                $emailText,
                '',
                ['%ACCOUNT%' => $accountText]
            );
        } catch (MailNotSent $e) {
            \yii::$app->session->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
        }

        return $user;
    }

    private function addUsersByEmail()
    {
        $params   = $this->getParams();
        $post     = \Yii::$app->request->post();
        $hasEmail = ($params->mailService['transport'] !== 'none');

        $emails    = explode("\n", $post['emailAddresses']);
        $names     = explode("\n", $post['names']);
        $passwords = ($hasEmail ? null : explode("\n", $post['passwords']));

        if (count($emails) !== count($names)) {
            \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
        } elseif (!$hasEmail && count($emails) !== count($passwords)) {
            \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
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
                \Yii::$app->session->setFlash('error', $errMsg);
            }
            if (count($alreadyExisted) > 0) {
                \Yii::$app->session->setFlash('info', \Yii::t('admin', 'siteacc_user_had') . ': ' .
                    implode(', ', $alreadyExisted));
            }
            if ($created > 0) {
                if ($created === 1) {
                    $msg = str_replace('%NUM%', $created, \Yii::t('admin', 'siteacc_user_added_x'));
                } else {
                    $msg = str_replace('%NUM%', $created, \Yii::t('admin', 'siteacc_user_added_x'));
                }
                \Yii::$app->session->setFlash('success', $msg);
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_user_added_0'));
            }
        }
    }

    /**
     * Hint: later it will be possible to select a group when inviting the user. Until then, it's a hard-coded group.
     */
    private function getDefaultUserGroup(): ?ConsultationUserGroup
    {
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->templateId === ConsultationUserGroup::TEMPLATE_PARTICIPANT) {
                return $userGroup;
            }
        }
        return null;
    }

    private function getConsultationAndCheckAdminPermission(): Consultation
    {
        $consultation = $this->consultation;

        if (!User::havePrivilege($consultation, ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            throw new ExitException();
        }

        return $consultation;
    }

    private function getUsersWidgetData(Consultation $consultation): array
    {
        $usersArr = array_map(function (User $user) use ($consultation): array {
            return $user->getUserAdminApiObject($consultation);
        }, $consultation->getUsersInAnyGroup());
        $groupsArr = array_map(function (ConsultationUserGroup $group): array {
            return $group->getUserAdminApiObject();
        }, $consultation->getAllAvailableUserGroups());

        return [
            'users' => $usersArr,
            'groups' => $groupsArr,
        ];
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
    private function setUserGroups(Consultation $consultation, int $userId, array $groupIds): void
    {
        $user = User::findOne(['id' => $userId]);
        $userHasGroups = [];

        // Remove all groups belonging to this consultation that are not in the sent array
        foreach ($user->userGroups as $userGroup) {
            $userHasGroups[] = $userGroup->id;

            if (!$userGroup->isRelevantForConsultation($consultation)) {
                continue;
            }
            if (!in_array($userGroup->id, $groupIds)) {
                $this->preventInvalidSiteAdminEdit($consultation, $userGroup);
                $this->preventRemovingMyself($consultation, $userGroup, $user);
                /** @noinspection PhpUnhandledExceptionInspection */
                $user->unlink('userGroups', $userGroup, true);
            }
        }

        foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
            if (in_array($userGroup->id, $groupIds) && !in_array($userGroup->id, $userHasGroups)) {
                $this->preventInvalidSiteAdminEdit($consultation, $userGroup);
                $user->link('userGroups', $userGroup);
            }
        }

        $consultation->refresh();
    }

    /**
     * @throws UserEditFailed
     */
    private function removeUser(Consultation $consultation, int $userId): void
    {
        $myself = User::getCurrentUser();
        if ($userId === $myself->id) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_lockout'));
        }

        $user = User::findOne(['id' => $userId]);
        if ($user->hasPrivilege($consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN) &&
            !$myself->hasPrivilege($consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            throw new UserEditFailed(\Yii::t('admin', 'siteacc_err_siteprivesc'));
        }

        foreach ($user->getUserGroupsForConsultation($consultation) as $userGroup) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $user->unlink('userGroups', $userGroup, true);
        }

        $consultation->refresh();
    }

    private function createUserGroup(Consultation $consultation, string $groupName): void
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $consultation->siteId;
        $group->consultationId = $consultation->id;
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
    private function removeUserGroup(Consultation $consultation, int $groupId): void
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
            $user->unlink('userGroups', $group, true);
            if (count($user->getUserGroupsForConsultation($consultation)) === 0) {
                $user->link('userGroups', $defaultGroup);
            }
        }
        $group->delete();
    }

    public function actionUsers(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        if ($this->isPostSet('addUsers')) {
            if (trim(\Yii::$app->request->post('emailAddresses', '')) !== '') {
                $this->addUsersByEmail();
            }
            if (trim(\Yii::$app->request->post('samlWW', '')) !== '' && $this->getParams()->isSamlActive()) {
                $this->addUsersBySamlWw();
            }
        }

        if ($this->isPostSet('grantAccess')) {
            $userIds = array_map('intval', \Yii::$app->request->post('userId', []));
            $defaultGroup = $this->getDefaultUserGroup();
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (!in_array($screeningUser->userId, $userIds)) {
                    continue;
                }
                $user = $screeningUser->user;
                $user->link('userGroups', $defaultGroup);
                /** @noinspection PhpUnhandledExceptionInspection */
                $screeningUser->delete();

                $consUrl = UrlHelper::createUrl('consultation/index');
                $consUrl = UrlHelper::absolutizeLink($consUrl);
                $emailText = str_replace('%LINK%', $consUrl, \Yii::t('user', 'access_granted_email'));

                MailTools::sendWithLog(
                    EMailLog::TYPE_ACCESS_GRANTED,
                    $this->consultation,
                    $user->email,
                    $user->id,
                    \Yii::t('user', 'acc_grant_email_title'),
                    $emailText
                );
            }
            $this->consultation->refresh();
        }

        if ($this->isPostSet('noAccess')) {
            $userIds = array_map('intval', \Yii::$app->request->post('userId', []));
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (in_array($screeningUser->userId, $userIds)) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $screeningUser->delete();
                }
            }
            $this->consultation->refresh();
        }

        return $this->render('users', [
            'widgetData' => $this->getUsersWidgetData($consultation),
            'screening' => $consultation->screeningUsers,
        ]);
    }

    public function actionUsersSave(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $additionalData = [
            'msg_success' => null,
            'msg_error' => null,
        ];
        try {
            switch (\Yii::$app->request->post('op')) {
                case 'save-user-groups':
                    $this->setUserGroups(
                        $consultation,
                        intval(\Yii::$app->request->post('userId')),
                        array_map('intval', \Yii::$app->request->post('groups', []))
                    );
                    break;
                case 'remove-user':
                    $this->removeUser($consultation, intval(\Yii::$app->request->post('userId')));
                    break;
                case 'create-user-group':
                    $this->createUserGroup($consultation, \Yii::$app->request->post('groupName'));
                    break;
                case 'remove-group':
                    $this->removeUserGroup($consultation, intval(\Yii::$app->request->post('groupId')));
                    break;
            }
        } catch (UserEditFailed $failed) {
            $additionalData['msg_error'] = $failed->getMessage();
        }

        return $this->returnRestResponse(200, json_encode(array_merge(
            $this->getUsersWidgetData($consultation),
            $additionalData
        )));
    }

    public function actionUsersPoll(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $responseData = $this->getUsersWidgetData($consultation);
        return $this->returnRestResponse(200, json_encode($responseData));
    }
}
