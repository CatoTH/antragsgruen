<?php

namespace app\controllers\admin;

use app\components\UrlHelper;
use app\models\db\ConsultationUserPrivilege;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\exceptions\AlreadyExists;
use app\models\exceptions\MailNotSent;
use app\models\policies\IPolicy;
use \app\components\mail\Tools as MailTools;
use app\models\settings\AntragsgruenApp;
use yii\db\IntegrityException;

/**
 * @property Site $site
 * @property Consultation $consultation
 * @method render(string $view, array $options)
 * @method isPostSet(string $name)
 */
trait SiteAccessTrait
{
    /**
     * @param User $user
     * @param string $username
     */
    private function linkConsultationAdmin(User $user, $username)
    {
        try {
            $privilege = $this->consultation->getUserPrivilege($user);

            $privilege->privilegeView    = 1;
            $privilege->privilegeCreate  = 1;
            $privilege->adminSuper       = 1;
            $privilege->adminScreen      = 1;
            $privilege->adminContentEdit = 1;
            $privilege->save();

            $str = \Yii::t('admin', 'siteacc_admin_add_done');
            \Yii::$app->session->setFlash('success', str_replace('%username%', $username, $str));

            $this->consultation->refresh();
            $this->site->refresh();
        } catch (IntegrityException $e) {
            if (mb_strpos($e->getMessage(), 1062) !== false) {
                $str = str_replace('%username%', $username, \Yii::t('admin', 'siteacc_admin_add_had'));
                \Yii::$app->session->setFlash('success_login', $str);
            } else {
                \Yii::$app->session->setFlash('error_login', \Yii::t('base', 'err_unknown'));
            }
        }
    }

    /**
     * @param User $user
     */
    private function unlinkConsultationAdmin(User $user)
    {
        $privilege = $this->consultation->getUserPrivilege($user);

        $privilege->adminSuper       = 0;
        $privilege->adminScreen      = 0;
        $privilege->adminContentEdit = 0;
        $privilege->save();
    }

    /**
     */
    private function saveAdmins()
    {
        $permissions = \Yii::$app->request->post('adminType');
        foreach ($permissions as $userId => $type) {
            if ($userId == User::getCurrentUser()->id) {
                continue;
            }

            $user = User::findOne($userId);
            $privilege = $this->consultation->getUserPrivilege($user);

            if ($type == 'site') {
                try {
                    $this->site->link('admins', $user);
                } catch (\Exception $e) {
                }

                $privilege->adminSuper       = 0;
                $privilege->adminScreen      = 0;
                $privilege->adminContentEdit = 0;
            } else {
                $this->site->unlink('admins', $user, true);

                $privilege->privilegeCreate  = 1;
                $privilege->privilegeView    = 1;
                $privilege->adminSuper       = 1;
                $privilege->adminScreen      = 1;
                $privilege->adminContentEdit = 1;
            }

            $privilege->save();
        }
    }

    /**
     * @param string $username
     */
    private function addAdminWurzelwerk($username)
    {
        $newUser = User::findOne(['auth' => User::wurzelwerkId2Auth($username)]);
        if (!$newUser) {
            $newUser         = new User();
            $newUser->auth   = User::wurzelwerkId2Auth($username);
            $newUser->status = User::STATUS_CONFIRMED;
            $newUser->name   = '';
            $newUser->email  = '';
            $newUser->save();
        }
        /** @var User $newUser */
        $this->linkConsultationAdmin($newUser, $username);
    }

    private function addAdminEmail($email)
    {
        $newUser = User::findOne(['auth' => 'email:' . $email]);
        if (!$newUser) {
            $newPassword             = User::createPassword();
            $newUser                 = new User();
            $newUser->auth           = 'email:' . $email;
            $newUser->status         = User::STATUS_CONFIRMED;
            $newUser->email          = $email;
            $newUser->emailConfirmed = 1;
            $newUser->pwdEnc         = password_hash($newPassword, PASSWORD_DEFAULT);
            $newUser->name           = '';
            $newUser->save();

            $authText = \Yii::t('admin', 'siteacc_mail_yourdata');
            $authText = str_replace(['%EMAIL%', '%PASSWORD%'], [$email, $newPassword], $authText);
        } else {
            $authText = \Yii::t('admin', 'siteacc_mail_youracc');
            $authText = str_replace('%EMAIL%', $email, $authText);
        }
        /** @var User $newUser */
        $this->linkConsultationAdmin($newUser, $email);

        $subject = \Yii::t('admin', 'sitacc_admmail_subj');
        $link    = UrlHelper::createUrl('consultation/index');
        $link    = UrlHelper::absolutizeLink($link);
        $text    = str_replace(['%LINK%', '%ACCOUNT%'], [$link, $authText], \Yii::t('admin', 'sitacc_admmail_body'));
        try {
            MailTools::sendWithLog(EMailLog::TYPE_SITE_ADMIN, $this->site, $email, $newUser->id, $subject, $text);
        } catch (MailNotSent $e) {
            $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
            \yii::$app->session->setFlash('error', $errMsg);
        }
    }

    /**
     */
    private function addUsers()
    {
        /** @var AntragsgruenApp $params */
        $params   = \Yii::$app->params;
        $post     = \Yii::$app->request->post();
        $hasEmail = ($params->mailService['transport'] != 'none');

        $emails    = explode("\n", $post['emailAddresses']);
        $names     = explode("\n", $post['names']);
        $passwords = ($hasEmail ? null : explode("\n", $post['passwords']));

        if (count($emails) != count($names)) {
            \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
        } elseif (!$hasEmail && count($emails) != count($passwords)) {
            \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_err_linenumber'));
        } else {
            $errors         = [];
            $alreadyExisted = [];
            $created        = 0;

            for ($i = 0; $i < count($emails); $i++) {
                if ($emails[$i] == '') {
                    continue;
                }
                try {
                    ConsultationUserPrivilege::createWithUser(
                        $this->consultation,
                        trim($emails[$i]),
                        trim($names[$i]),
                        ($hasEmail ? $post['emailText'] : ''),
                        ($hasEmail ? null : $passwords[$i])
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
                if ($created == 1) {
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
     */
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

    /**
     */
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

    /**
     * @return bool
     */
    private function needsPolicyWarning()
    {
        $policyWarning = false;
        if (!$this->site->getSettings()->forceLogin && count($this->consultation->userPrivileges) > 0) {
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

    /**
     * @throws \Exception
     * @return string
     */
    public function actionSiteaccess()
    {
        $site = $this->site;

        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SITE_ADMIN)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return false;
        }

        $post = \Yii::$app->request->post();

        if ($this->isPostSet('saveLogin')) {
            $settings                      = $site->getSettings();
            $settings->forceLogin          = isset($post['forceLogin']);
            $settings->managedUserAccounts = isset($post['managedUserAccounts']);
            if ($this->isPostSet('login')) {
                $settings->loginMethods = $post['login'];
            } else {
                $settings->loginMethods = [];
            }
            if (User::getCurrentUser()->getAuthType() == \app\models\settings\Site::LOGIN_STD) {
                $settings->loginMethods[] = \app\models\settings\Site::LOGIN_STD;
            }
            if (User::getCurrentUser()->getAuthType() == \app\models\settings\Site::LOGIN_EXTERNAL) {
                $settings->loginMethods[] = \app\models\settings\Site::LOGIN_EXTERNAL;
            }
            $site->setSettings($settings);
            if ($site->save()) {
                \yii::$app->session->setFlash('success_login', \Yii::t('base', 'saved'));
            } else {
                \yii::$app->session->setFlash('error_login', 'An error occurred: ' . print_r($site->getErrors(), true));
            }
            $site->refresh();
        }

        if ($this->isPostSet('addAdmin')) {
            switch ($post['addType']) {
                case 'wurzelwerk':
                    $this->addAdminWurzelwerk($post['addUsername']);
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
            if ($todel && $todel->id != User::getCurrentUser()->id) {
                $this->site->unlink('admins', $todel, true);
                $this->unlinkConsultationAdmin($todel);
                \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_admin_del_done'));
            } else {
                \Yii::$app->session->setFlash('error', \Yii::t('admin', 'siteacc_admin_del_notf'));
            }
        }

        if ($this->isPostSet('saveUsers')) {
            $this->saveUsers();
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'siteacc_user_saved'));
        }

        if ($this->isPostSet('addUsers')) {
            $this->addUsers();
        }

        if ($this->isPostSet('policyRestrictToUsers')) {
            $this->restrictToUsers();
            \Yii::$app->session->setFlash('success_login', \Yii::t('admin', 'siteacc_user_restr_done'));
        }

        $policyWarning = $this->needsPolicyWarning();

        $admins = [];
        foreach ($site->admins as $admin) {
            $admins[$admin->id] = ["user" => $admin, "type" => 'site'];
        }
        foreach ($this->consultation->userPrivileges as $privilege) {
            $isAdmin = ($privilege->adminSuper || $privilege->adminScreen || $privilege->adminContentEdit);
            if (!isset($admins[$privilege->userId]) && $isAdmin) {
                $admins[$privilege->userId] = ["user" => $privilege->user, "type" => 'consultation'];
            }
        }

        return $this->render('site_access', ['site' => $site, 'policyWarning' => $policyWarning, 'admins' => $admins]);
    }
}
