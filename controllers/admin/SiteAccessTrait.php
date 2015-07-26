<?php

namespace app\controllers\admin;

use app\models\db\ConsultationUserPrivilege;
use app\models\db\Site;
use app\models\db\Consultation;
use app\models\db\User;
use app\models\exceptions\AlreadyExists;
use app\models\policies\IPolicy;

/**
 * @property Site $site
 * @property Consultation $consultation
 * @method render(string $view, array $options)
 */
trait SiteAccessTrait
{
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
            /** @var User $newUser */
            $username = $_POST['username'];
            if (strpos($username, '@') !== false) {
                $newUser = User::findOne(['auth' => 'email:' . $username]);
            } else {
                $newUser = User::findOne(['auth' => User::wurzelwerkId2Auth($username)]);
            }
            if ($newUser) {
                try {
                    $this->site->link('admins', $newUser);
                    $str = '%username% hat nun auch Admin-Rechte.';
                    \Yii::$app->session->setFlash('success', str_replace('%username%', $username, $str));
                } catch (\yii\db\IntegrityException $e) {
                    if (mb_strpos($e->getMessage(), 1062) !== false) {
                        $str = str_replace('%username%', $username, '%username% hatte bereits Admin-Rechte.');
                        \Yii::$app->session->setFlash('success', $str);
                    } else {
                        \Yii::$app->session->setFlash('error', 'Ein unbekannter Fehler ist aufgetreten');
                    }
                }
            } else {
                $str = 'BenutzerIn %username% nicht gefunden. Der/Diejenige muss sich zuvor mindestens ' .
                    'einmal auf Antragsgrün eingeloggt haben, um als Admin hinzugefügt werden zu können.';
                \Yii::$app->session->setFlash('error', str_replace('%username%', $username, $str));
            }
        }

        if (isset($_POST['removeAdmin'])) {
            /** @var User $todel */
            $todel = User::findOne($_POST['removeAdmin']);
            if ($todel) {
                $this->site->unlink('admins', $todel, true);
                \Yii::$app->session->setFlash('success', 'Die Admin-Rechte wurden entzogen.');
            } else {
                \Yii::$app->session->setFlash('error', 'Es gibt keinen Zugang mit diesem Namen');
            }
        }

        if (isset($_POST['saveUsers'])) {
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
            \Yii::$app->session->setFlash('success', 'Die Berechtigungen wurden gespeichert.');
        }

        if (isset($_POST['addUsers'])) {
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

        if (isset($_POST['policyRestrictToUsers'])) {
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
            $msg = 'Nur noch eingetragene BenutzerInnen können Einträge erstellen.';
            \Yii::$app->session->setFlash('success_login', $msg);
        }

        $policyWarning = false;
        if (!$site->getSettings()->forceLogin && count($this->consultation->userPrivileges) > 0) {
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

        return $this->render('site_access', ['site' => $site, 'policyWarning' => $policyWarning]);
    }
}
