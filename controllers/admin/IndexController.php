<?php

namespace app\controllers\admin;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ConsultationUserPrivilege;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;
use app\models\AdminTodoItem;
use app\models\exceptions\AlreadyExists;

class IndexController extends AdminBase
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        /** @var AdminTodoItem[] $todo */
        $todo = [];

        if (!is_null($this->consultation)) {
            $motions = Motion::getScreeningMotions($this->consultation);
            foreach ($motions as $motion) {
                $description = 'Von: ' . $motion->getInitiatorsStr();
                $todo[]      = new AdminTodoItem(
                    'motionScreen' . $motion->id,
                    $motion->getTitleWithPrefix(),
                    'Antrag freischalten',
                    UrlHelper::createUrl(['admin/motion/update', 'motionId' => $motion->id]),
                    $description
                );
            }
            $amendments = Amendment::getScreeningAmendments($this->consultation);
            foreach ($amendments as $amend) {
                $description = 'Von: ' . $amend->getInitiatorsStr();
                $todo[]      = new AdminTodoItem(
                    'amendmentsScreen' . $amend->id,
                    $amend->getTitle(),
                    'Änderungsantrag freischalten',
                    UrlHelper::createUrl(['admin/amendment/update', 'amendmentId' => $amend->id]),
                    $description
                );
            }
            // @TODO Comments
        }

        return $this->render(
            'index',
            [
                'todo'         => $todo,
                'site'         => $this->site,
                'consultation' => $this->consultation
            ]
        );
    }

    /**
     * @return string
     * @throws \app\models\exceptions\FormError
     */
    public function actionConsultation()
    {
        $model = $this->consultation;

        $locale = Tools::getCurrentDateLocale();

        if (isset($_POST['save'])) {
            $data = $_POST['consultation'];
            $model->setAttributes($data);

            $settingsInput = (isset($_POST['settings']) ? $_POST['settings'] : []);
            $settings      = $model->getSettings();
            $settings->saveForm($settingsInput, $_POST['settingsFields']);
            $model->setSettings($settings);

            if ($model->save()) {
                $model->flushCaches();
                \yii::$app->session->setFlash('success', 'Gespeichert.');
            } else {
                \yii::$app->session->setFlash('error', print_r($model->getErrors(), true));
            }
        }

        return $this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]);
    }

    /**
     * @param Consultation $consultation
     */
    private function saveTags(Consultation $consultation)
    {
        if (!isset($_POST['tags'])) {
            return;
        }

        /**
         * @param int $tagId
         * @return ConsultationSettingsTag|null
         */
        $getById = function ($tagId) use ($consultation) {
            foreach ($consultation->tags as $tag) {
                if ($tag->id == $tagId) {
                    return $tag;
                }
            }
            return null;
        };
        /**
         * @param string $tagName
         * @return ConsultationSettingsTag|null
         */
        $getByName = function ($tagName) use ($consultation) {
            $tagName = mb_strtolower($tagName);
            foreach ($consultation->tags as $tag) {
                if (mb_strtolower($tag->title) == $tagName) {
                    return $tag;
                }
            }
            return null;
        };

        $foundTags = [];
        $newTags   = json_decode($_POST['tags'], true);
        foreach ($newTags as $pos => $newTag) {
            if ($newTag['id'] == 0) {
                if ($getByName($newTag['name'])) {
                    continue;
                }
                $tag                 = new ConsultationSettingsTag();
                $tag->consultationId = $consultation->id;
                $tag->title          = $newTag['name'];
                $tag->position       = $pos;
                $tag->save();
            } else {
                $tag = $getById($newTag['id']);
                if (!$tag) {
                    continue;
                }
                /** @var ConsultationSettingsTag $tag */
                $tag->position = $pos;
                $tag->save();
            }
            $foundTags[] = $tag->id;
        }

        foreach ($consultation->tags as $tag) {
            if (!in_array($tag->id, $foundTags)) {
                foreach ($tag->motions as $motion) {
                    $motion->unlink('tags', $tag, false);
                }
                $tag->delete();
            }
        }

        $consultation->refresh();
    }

    /**
     * @param Site $site
     */
    private function saveSiteSettings(Site $site)
    {
        $ssettings                = (isset($_POST['siteSettings']) ? $_POST['siteSettings'] : []);
        $siteSettings             = $site->getSettings();
        $siteSettings->siteLayout = $ssettings['siteLayout'];
        $site->setSettings($siteSettings);
        $site->save();

    }

    /**
     * @throws \Exception
     * @throws \app\models\exceptions\FormError
     * @return string
     */
    public function actionConsultationextended()
    {
        $consultation = $this->consultation;

        $this->saveTags($consultation);

        if (isset($_POST['save'])) {
            //$consultation->policySupport = $_POST['consultation']['policySupport'];

            $settingsInput = (isset($_POST['settings']) ? $_POST['settings'] : []);
            $settings      = $consultation->getSettings();
            $settings->saveForm($settingsInput, $_POST['settingsFields']);
            $consultation->setSettings($settings);

            if ($consultation->save()) {
                $this->saveSiteSettings($consultation->site);

                if (!$consultation->getSettings()->adminsMayEdit) {
                    foreach ($consultation->motions as $motion) {
                        $motion->textFixed = 1;
                        $motion->save(false);
                        foreach ($motion->amendments as $amend) {
                            $amend->textFixed = 1;
                            $amend->save(true);
                        }
                    }
                }

                $consultation->flushCaches();
                \yii::$app->session->setFlash('success', 'Gespeichert.');
            } else {
                \yii::$app->session->setFlash('error', print_r($consultation->getErrors(), true));
            }
        }

        return $this->render('consultation_extended', ['consultation' => $consultation]);
    }

    /**
     * @throws \Exception
     * @return string
     */
    public function actionSiteaccess()
    {
        $site = $this->site;

        if (isset($_POST['save'])) {
            $settings             = $site->getSettings();
            $settings->forceLogin = isset($_POST['forceLogin']);
            if (isset($_POST['login'])) {
                $settings->loginMethods = $_POST['login'];
            } else {
                $settings->loginMethods = [];
            }
            $site->setSettings($settings);
            $site->save();

            \yii::$app->session->setFlash('success', 'Gespeichert.');
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
                            $emails[$i],
                            $names[$i],
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

        return $this->render('site_access', ['site' => $site]);
    }


    /**
     * @param string $category
     * @return string
     */
    public function actionTranslation($category = 'base')
    {
        $consultation = $this->consultation;

        if (isset($_POST['save']) && isset($_POST['wordingBase'])) {
            $consultation->wordingBase = $_POST['wordingBase'];
            $consultation->save();
            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        if (isset($_POST['save']) && isset($_POST['string'])) {
            foreach ($_POST['string'] as $key => $val) {
                $key   = urldecode($key);
                $found = false;
                foreach ($consultation->texts as $text) {
                    if ($text->category == $category && $text->textId == $key) {
                        if ($val == '') {
                            $text->delete();
                        } else {
                            $text->text = $val;
                            $text->save();
                        }
                        $found = true;
                    }
                }
                if (!$found && $val != '') {
                    $text                 = new ConsultationText();
                    $text->consultationId = $consultation->id;
                    $text->category       = $category;
                    $text->textId         = $key;
                    $text->text           = $val;
                    $text->editDate       = date('Y-m-d H:i:s');
                    $text->save();
                }
            }
            $consultation->refresh();
            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        return $this->render('translation', ['consultation' => $consultation, 'category' => $category]);
    }
}
