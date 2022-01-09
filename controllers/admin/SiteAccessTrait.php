<?php

namespace app\controllers\admin;

use app\components\{UrlHelper, mail\Tools as MailTools};
use app\models\db\{ConsultationUserGroup, EMailLog, Site, Consultation, User};
use app\models\exceptions\{AlreadyExists, MailNotSent};
use app\models\policies\IPolicy;
use app\models\settings\AntragsgruenApp;
use yii\base\ExitException;
use yii\db\IntegrityException;
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
    private function linkConsultationAdmin(User $user, string $username): void
    {
        try {
            $privilege = $this->consultation->getUserPrivilege($user);

            $privilege->privilegeView    = 1;
            $privilege->privilegeCreate  = 1;
            $privilege->adminSuper       = 1;
            $privilege->adminScreen      = 1;
            $privilege->adminContentEdit = 1;
            $privilege->adminProposals   = 1;
            $privilege->save();

            $str = \Yii::t('admin', 'siteacc_admin_add_done');
            \Yii::$app->session->setFlash('success', str_replace('%username%', $username, $str));

            $this->consultation->refresh();
            $this->site->refresh();
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (IntegrityException $e) {
            if (mb_strpos($e->getMessage(), 1062) !== false) {
                $str = str_replace('%username%', $username, \Yii::t('admin', 'siteacc_admin_add_had'));
                \Yii::$app->session->setFlash('success_login', $str);
            } else {
                \Yii::$app->session->setFlash('error_login', \Yii::t('base', 'err_unknown'));
            }
        }
    }

    private function unlinkConsultationAdmin(User $user): void
    {
        $privilege = $this->consultation->getUserPrivilege($user);

        $privilege->adminSuper       = 0;
        $privilege->adminScreen      = 0;
        $privilege->adminContentEdit = 0;
        $privilege->adminProposals   = 0;
        $privilege->save();
    }

    private function saveAdmins(): void
    {
        $replyTos    = \Yii::$app->request->post('ppReplyTo', []);
        foreach ($replyTos as $userId => $replyTo) {
            $user                = User::findOne($userId);
            $settings            = $user->getSettingsObj();
            if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $settings->ppReplyTo = $replyTos[$userId];
            } else {
                $settings->ppReplyTo = '';
            }
            $user->setSettingsObj($settings);
            $user->save();
        }

        $permissions = \Yii::$app->request->post('adminTypes', []);
        foreach ($permissions as $userId => $types) {
            if ($userId === User::getCurrentUser()->id) {
                continue;
            }

            $user      = User::findOne($userId);
            $privilege = $this->consultation->getUserPrivilege($user);

            if (in_array('site', $types)) {
                try {
                    $this->site->link('admins', $user);
                } catch (\Exception $e) {
                }

                $privilege->adminSuper       = 0;
                $privilege->adminScreen      = 0;
                $privilege->adminContentEdit = 0;
                $privilege->adminProposals   = 0;
            } else {
                $this->site->unlink('admins', $user, true);

                $privilege->privilegeCreate = 1;
                $privilege->privilegeView   = 1;
                if (in_array('consultation', $types)) {
                    $privilege->adminSuper       = 1;
                    $privilege->adminScreen      = 1;
                    $privilege->adminContentEdit = 1;
                } else {
                    $privilege->adminSuper       = 0;
                    $privilege->adminScreen      = 0;
                    $privilege->adminContentEdit = 0;
                }
                if (in_array('proposal', $types)) {
                    $privilege->adminProposals = 1;
                } else {
                    $privilege->adminProposals = 0;
                }
            }

            $privilege->save();
        }
    }

    private function addAdminGruenesNetz(string $username): void
    {
        $newUser = User::findByAuthTypeAndName(\app\models\settings\Site::LOGIN_GRUENES_NETZ, $username);
        if (!$newUser) {
            $newUser                  = new User();
            $newUser->auth            = User::gruenesNetzId2Auth($username);
            $newUser->status          = User::STATUS_CONFIRMED;
            $newUser->name            = '';
            $newUser->email           = '';
            $newUser->organizationIds = '';
            $newUser->save();
        }
        $this->linkConsultationAdmin($newUser, $username);
    }

    /**
     * @param string $email
     * @throws \Yii\base\Exception
     */
    private function addAdminEmail($email)
    {
        $newUser = User::findByAuthTypeAndName(\app\models\settings\Site::LOGIN_STD, $email);
        if (!$newUser) {
            $newPassword              = User::createPassword();
            $newUser                  = new User();
            $newUser->auth            = 'email:' . $email;
            $newUser->status          = User::STATUS_CONFIRMED;
            $newUser->email           = $email;
            $newUser->emailConfirmed  = 1;
            $newUser->pwdEnc          = password_hash($newPassword, PASSWORD_DEFAULT);
            $newUser->name            = '';
            $newUser->organizationIds = '';
            $newUser->save();

            $authText = \Yii::t('admin', 'siteacc_mail_yourdata');
            $authText = str_replace(['%EMAIL%', '%PASSWORD%'], [$email, $newPassword], $authText);
        } else {
            $authText = \Yii::t('admin', 'siteacc_mail_youracc');
            $authText = str_replace('%EMAIL%', $email, $authText);
        }
        $this->linkConsultationAdmin($newUser, $email);

        $subject = \Yii::t('admin', 'sitacc_admmail_subj');
        $link    = UrlHelper::createUrl('consultation/index');
        $link    = UrlHelper::absolutizeLink($link);
        $text    = str_replace(['%LINK%', '%ACCOUNT%'], [$link, $authText], \Yii::t('admin', 'sitacc_admmail_body'));
        try {
            $consultation = $this->consultation;
            MailTools::sendWithLog(EMailLog::TYPE_SITE_ADMIN, $consultation, $email, $newUser->id, $subject, $text);
        } catch (MailNotSent $e) {
            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
            \Yii::$app->session->setFlash('error', $errMsg);
        }
    }

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
            $user                  = new User();
            $user->auth            = $auth;
            $user->email           = '';
            $user->name            = '';
            $user->emailConfirmed  = 0;
            $user->pwdEnc          = null;
            $user->status          = User::STATUS_CONFIRMED;
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

    private function saveUsers()
    {
        $postAccess = \Yii::$app->request->post('access');
        foreach ($this->consultation->userPrivileges as $privilege) {
            if (isset($postAccess[$privilege->userId])) {
                $access                     = $postAccess[$privilege->userId];
                $privilege->privilegeView   = (in_array('view', $access) ? 1 : 0);
                $privilege->privilegeCreate = (in_array('create', $access) ? 1 : 0);
            } else {
                $privilege->privilegeView   = 0;
                $privilege->privilegeCreate = 0;
            }
            $privilege->save();
        }
    }

    private function restrictToUsers()
    {
        $allowed = [IPolicy::POLICY_NOBODY, IPolicy::POLICY_LOGGED_IN, IPolicy::POLICY_LOGGED_IN];
        foreach ($this->consultation->motionTypes as $type) {
            if (!in_array($type->policyMotions, $allowed)) {
                $type->policyMotions = IPolicy::POLICY_LOGGED_IN;
            }
            if (!in_array($type->policyAmendments, $allowed)) {
                $type->policyAmendments = IPolicy::POLICY_LOGGED_IN;
            }
            if (!in_array($type->policyComments, $allowed)) {
                $type->policyComments = IPolicy::POLICY_LOGGED_IN;
            }
            if (!in_array($type->policySupportMotions, $allowed)) {
                $type->policySupportMotions = IPolicy::POLICY_LOGGED_IN;
            }
            if (!in_array($type->policySupportAmendments, $allowed)) {
                $type->policySupportAmendments = IPolicy::POLICY_LOGGED_IN;
            }
            $type->save();
        }
    }

    /*
     * This checks if there are regular users manually registered for this consultation,
     * but no restriction like "only registered users may create motions" or "force login to view the page" is set up.
     * If so, a warning should be shown.
     */
    private function needsPolicyWarning(): bool
    {
        $policyWarning = false;

        $siteAdminIds = array_map(function(User $user): int {
            return $user->id;
        }, $this->consultation->site->admins);

        $usersWithReadWriteAccess = false;
        foreach ($this->consultation->userPrivileges as $privilege) {
            // Users that have regular privilges, not consultation/site-admins
            if (($privilege->privilegeCreate || $privilege->privilegeView) && !(
                $privilege->adminContentEdit || $privilege->adminProposals || $privilege->adminScreen || $privilege->adminSuper ||
                in_array($privilege->userId, $siteAdminIds)
            )) {
                $usersWithReadWriteAccess = true;
            }
        }

        if (!$this->consultation->getSettings()->forceLogin && $usersWithReadWriteAccess) {
            $allowed = [IPolicy::POLICY_NOBODY, IPolicy::POLICY_LOGGED_IN, IPolicy::POLICY_LOGGED_IN];
            foreach ($this->consultation->motionTypes as $type) {
                if (!in_array($type->policyMotions, $allowed)) {
                    $policyWarning = true;
                }
                if (!in_array($type->policyAmendments, $allowed)) {
                    $policyWarning = true;
                }
                if (!in_array($type->policyComments, $allowed)) {
                    $policyWarning = true;
                }
                if (!in_array($type->policySupportMotions, $allowed)) {
                    $policyWarning = true;
                }
                if (!in_array($type->policySupportAmendments, $allowed)) {
                    $policyWarning = true;
                }
            }
        }
        return $policyWarning;
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
        $usersArr = array_map(function (User $user): array {
            return $user->getUserAdminApiObject();
        }, $consultation->getUsersInAnyGroup());
        $groupsArr = array_map(function (ConsultationUserGroup $group): array {
            return $group->getUserAdminApiObject();
        }, $consultation->getAllAvailableUserGroups());

        return [
            'users' => $usersArr,
            'groups' => $groupsArr,
        ];
    }

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
                $user->unlink('userGroups', $userGroup, true);
            }
        }

        foreach ($consultation->getAllAvailableUserGroups() as $userGroup) {
            if (in_array($userGroup->id, $groupIds) && !in_array($userGroup->id, $userHasGroups)) {
                $user->link('userGroups', $userGroup);
            }
        }

        $consultation->refresh();
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

        return $this->render('users', [ 'widgetData' => $this->getUsersWidgetData($consultation) ]);
    }

    public function actionUsersSave(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        switch (\Yii::$app->request->post('op')) {
            case 'save-user-groups':
                $this->setUserGroups(
                    $consultation,
                    intval(\Yii::$app->request->post('userId')),
                    array_map('intval', \Yii::$app->request->post('groups', []))
                );
                break;
        }

        $responseData = $this->getUsersWidgetData($consultation);
        return $this->returnRestResponse(200, json_encode($responseData));
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

    /**
     * @throws \Yii\base\Exception|\Throwable
     */
    public function actionSiteaccess(): string
    {
        $site = $this->site;
        $con  = $this->consultation;

        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return '';
        }

        $post = \Yii::$app->request->post();

        if ($this->isPostSet('addAdmin')) {
            switch ($post['addType']) {
                case 'gruenesnetz':
                    $this->addAdminGruenesNetz($post['addUsername']);
                    break;
                case 'email':
                    $this->addAdminEmail($post['addUsername']);
                    break;
            }
        }

        if ($this->isPostSet('saveAdmin')) {
            $this->saveAdmins();
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_user_saved'));
        }

        if ($this->isPostSet('removeAdmin')) {
            /** @var User $todel */
            $todel = User::findOne($post['removeAdmin']);
            if ($todel && $todel->id !== User::getCurrentUser()->id) {
                $this->site->unlink('admins', $todel, true);
                $this->unlinkConsultationAdmin($todel);
                \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_admin_del_done'));
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_admin_del_notf'));
            }
        }

        if ($this->isPostSet('grantAccess') && isset($post['userId']) && count($post['userId']) > 0) {
            foreach ($this->consultation->userPrivileges as $privilege) {
                if (in_array($privilege->userId, $post['userId'])) {
                    $privilege->grantPermission();
                }
            }
        }

        if ($this->isPostSet('noAccess') && isset($post['userId']) && count($post['userId']) > 0) {
            foreach ($this->consultation->userPrivileges as $privilege) {
                if (in_array($privilege->userId, $post['userId'])) {
                    $privilege->delete();
                }
            }
            $this->consultation->refresh();
        }

        if ($this->isPostSet('deleteUser')) {
            $toDeleteUserId = IntVal($post['deleteUser']);
            foreach ($this->consultation->userPrivileges as $privilege) {
                if ($privilege->userId === $toDeleteUserId) {
                    $privilege->delete();
                    \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_user_del_done'));
                }
            }
            $this->consultation->refresh();
        } elseif ($this->isPostSet('saveUsers')) {
            $this->saveUsers();
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_user_saved'));
        } elseif ($this->isPostSet('addUsers')) {
            if (trim(\Yii::$app->request->post('emailAddresses', '')) !== '') {
                $this->addUsersByEmail();
            }
            if (trim(\Yii::$app->request->post('samlWW', '')) !== '' && $this->getParams()->isSamlActive()) {
                $this->addUsersBySamlWw();
            }
        }

        if ($this->isPostSet('policyRestrictToUsers')) {
            $this->restrictToUsers();
            \Yii::$app->session->setFlash('success_login', \Yii::t('admin', 'siteacc_user_restr_done'));
        }

        $policyWarning = $this->needsPolicyWarning();

        $admins = [];
        foreach ($site->admins as $admin) {
            $admins[$admin->id] = ['user' => $admin, 'types' => ['site']];
        }
        foreach ($this->consultation->userPrivileges as $privilege) {
            if (!isset($admins[$privilege->userId])) {
                if (!$privilege->user) {
                    continue; // User is deleted and an obsolete privilege entry remains
                }
                $privileges = [];
                if ($privilege->adminProposals) {
                    $privileges[] = 'proposal';
                }
                if ($privilege->adminSuper || $privilege->adminScreen || $privilege->adminContentEdit) {
                    $privileges[] = 'consultation';
                }
                if (count($privileges) > 0) {
                    $admins[$privilege->userId] = ['user' => $privilege->user, 'types' => $privileges];
                }
            }
        }

        return $this->render('site_access', [
            'consultation'  => $this->consultation,
            'site'          => $site,
            'policyWarning' => $policyWarning,
            'admins'        => $admins,
        ]);
    }
}
