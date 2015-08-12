<?php

namespace app\controllers\admin;

use app\components\UrlHelper;
use app\models\db\ConsultationUserPrivilege;
use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\exceptions\AlreadyExists;
use app\models\policies\IPolicy;
use yii\db\IntegrityException;

/**
 * @property Site $site
 * @property Consultation $consultation
 * @method render(string $view, array $options)
 */
trait SiteAccessTrait
{
    /**
     * @param User $user
     * @param string $username
     */
    private function linkAdmin(User $user, $username)
    {
        try {
            $this->site->link('admins', $user);
            $str = '%username% hat nun auch Admin-Rechte.';
            \Yii::$app->session->setFlash('success', str_replace('%username%', $username, $str));
        } catch (IntegrityException $e) {
            if (mb_strpos($e->getMessage(), 1062) !== false) {
                $str = str_replace('%username%', $username, '%username% hatte bereits Admin-Rechte.');
                \Yii::$app->session->setFlash('success_login', $str);
            } else {
                \Yii::$app->session->setFlash('error_login', 'Ein unbekannter Fehler ist aufgetreten');
            }
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
        $this->linkAdmin($newUser, $username);
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

            $authText = "Du kannst dich mit folgenden Angaben einloggen:\nBenutzerInnenname: %EMAIL%\n" .
                "Passwort: %PASSWORD%";
            $authText = str_replace(['%EMAIL%', '%PASSWORD%'], [$email, $newPassword], $authText);
        } else {
            $authText = 'Du kannst dich mit deinem BenutzerInnenname %EMAIL% einloggen.';
            $authText = str_replace('%EMAIL%', $email, $authText);
        }
        /** @var User $newUser */
        $this->linkAdmin($newUser, $email);

        $subject = 'Antragsgrün-Administration';
        $link    = UrlHelper::createUrl('consultation/index');
        $link    = UrlHelper::absolutizeLink($link);
        $text    = "Hallo!\n\nDu hast eben Admin-Zugang zu folgender Antragsgrün-Seite bekommen: %LINK%\n\n" .
            "%ACCOUNT%\n\nLiebe Grüße,\n  Das Antragsgrün-Team";
        $text    = str_replace(['%LINK%', '%ACCOUNT%'], [$link, $authText], $text);
        \app\components\mail\Tools::sendWithLog(EMailLog::TYPE_SITE_ADMIN, $this->site, $email, $newUser->id, $subject, $text);
    }

    /**
     */
    private function addUsers()
    {
        $emails = explode("\n", $_POST['emailAddresses']);
        $names  = explode("\n", $_POST['names']);
        if (count($emails) != count($names)) {
            $msg = 'Die Zahl der E-Mail-Adressen und der Namen stimmt nicht überein';
            \Yii::$app->session->setFlash('error', $msg);
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
                        $_POST['emailText']
                    );
                    $created++;
                } catch (AlreadyExists $e) {
                    $alreadyExisted[] = $emails[$i];
                } catch (\Exception $e) {
                    $errors[] = $emails[$i] . ': ' . $e->getMessage();
                }
            }
            if (count($errors) > 0) {
                \Yii::$app->session->setFlash('error', 'Es sind Fehler aufgetreten: ' . implode(', ', $errors));
            }
            if (count($alreadyExisted) > 0) {
                \Yii::$app->session->setFlash('info', 'Folgende BenutzerInnen hatten bereits Zugriff: ' .
                    implode(', ', $alreadyExisted));

            }
            if ($created > 0) {
                if ($created == 1) {
                    $msg = str_replace('%NUM%', $created, '%NUM% BenutzerIn wurde eingetragen.');
                } else {
                    $msg = str_replace('%NUM%', $created, '%NUM% BenutzerInnen wurden eingetragen.');
                }
                \Yii::$app->session->setFlash('success', $msg);
            } else {
                \Yii::$app->session->setFlash('error', 'Es wurde niemand eingetragen.');
            }
        }
    }

    /**
     */
    private function saveUsers()
    {
        foreach ($this->consultation->userPrivileges as $privilege) {
            if (isset($_POST['access'][$privilege->userId])) {
                $access                     = $_POST['access'][$privilege->userId];
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
            if (!in_array($type->policySupport, $allowed)) {
                $type->policySupport = IPolicy::POLICY_LOGGED_IN;
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
                if (!in_array($type->policySupport, $allowed)) {
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

        if (isset($_POST['saveLogin'])) {
            $settings                      = $site->getSettings();
            $settings->forceLogin          = isset($_POST['forceLogin']);
            $settings->managedUserAccounts = isset($_POST['managedUserAccounts']);
            if (isset($_POST['login'])) {
                $settings->loginMethods = $_POST['login'];
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
            $site->save();

            \yii::$app->session->setFlash('success_login', 'Gespeichert.');
        }

        if (isset($_POST['addAdmin'])) {
            switch ($_POST['addType']) {
                case 'wurzelwerk':
                    $this->addAdminWurzelwerk($_POST['addUsername']);
                    break;
                case 'email':
                    $this->addAdminEmail($_POST['addUsername']);
                    break;
            }
        }

        if (isset($_POST['removeAdmin'])) {
            /** @var User $todel */
            $todel = User::findOne($_POST['removeAdmin']);
            if ($todel) {
                $this->site->unlink('admins', $todel, true);
                \Yii::$app->session->setFlash('success_login', 'Die Admin-Rechte wurden entzogen.');
            } else {
                \Yii::$app->session->setFlash('error_login', 'Es gibt keinen Zugang mit diesem Namen');
            }
        }

        if (isset($_POST['saveUsers'])) {
            $this->saveUsers();
            \Yii::$app->session->setFlash('success', 'Die Berechtigungen wurden gespeichert.');
        }

        if (isset($_POST['addUsers'])) {
            $this->addUsers();
        }

        if (isset($_POST['policyRestrictToUsers'])) {
            $this->restrictToUsers();
            $msg = 'Nur noch eingetragene BenutzerInnen können Einträge erstellen.';
            \Yii::$app->session->setFlash('success_login', $msg);
        }

        $policyWarning = $this->needsPolicyWarning();

        return $this->render('site_access', ['site' => $site, 'policyWarning' => $policyWarning]);
    }
}
