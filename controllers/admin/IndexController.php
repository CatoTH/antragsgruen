<?php

namespace app\controllers\admin;

use app\components\AntiXSS;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;
use app\models\AdminTodoItem;

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
            // @TODO Amendments & Comments
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
     */
    public function actionAdmins()
    {
        /** @var User $myself */
        $myself = \Yii::$app->user->identity;

        if (isset($_POST['adduser'])) {
            /** @var User $newUser */
            if (strpos($_REQUEST['username'], '@') !== false) {
                $newUser = User::findOne(['auth' => 'email:' . $_REQUEST['username']]);
            } else {
                $newUser = User::findOne(['auth' => User::wurzelwerkId2Auth($_REQUEST["username"])]);
            }
            if ($newUser) {
                $this->site->link('admins', $newUser);
                $str = '%username% hat nun auch Admin-Rechte.';
                \Yii::$app->session->setFlash('success', str_replace('%username%', $_REQUEST['username'], $str));
            } else {
                $str = 'BenutzerIn %username% nicht gefunden. Der/Diejenige muss sich zuvor mindestens ' .
                    'einmal auf Antragsgrün eingeloggt haben, um als Admin hinzugefügt werden zu können.';
                \Yii::$app->session->setFlash('error', str_replace('%username%', $_REQUEST['username'], $str));
            }
        }
        if (AntiXSS::isTokenSet('removeuser')) {
            /** @var User $todel */
            $todel = User::findOne(AntiXSS::getTokenVal('removeuser'));
            $this->site->unlink('admins', $todel, true);
            \Yii::$app->session->setFlash('success', 'Die Admin-Rechte wurden entzogen.');
        }

        $delform        = AntiXSS::createToken('removeuser');
        $delUrlTemplate = UrlHelper::createUrl(['admin/index/admins', $delform => 'REMOVEID']);
        return $this->render(
            'admins',
            [
                'site'   => $this->site,
                'myself' => $myself,
                'delUrl' => $delUrlTemplate,
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
            $model->deadlineMotions    = Tools::dateBootstraptime2sql($data['deadlineMotions'], $locale);
            $model->deadlineAmendments = Tools::dateBootstraptime2sql($data['deadlineAmendments'], $locale);

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
        if (AntiXSS::isTokenSet("delTag")) {
            foreach ($consultation->tags as $tag) {
                if ($tag->id == AntiXSS::getTokenVal("delTag")) {
                    $tag->delete();
                    $consultation->refresh();
                }
            }
        }


        if (isset($_POST["tagCreate"]) && trim($_POST["tagCreate"]) != "") {
            $maxId     = 0;
            $duplicate = false;
            foreach ($consultation->tags as $tag) {
                if ($tag->position > $maxId) {
                    $maxId = $tag->position;
                }
                if (mb_strtolower($tag->title) == mb_strtolower($_POST["tagCreate"])) {
                    $duplicate = true;
                }
            }
            if (!$duplicate) {
                $tag                 = new ConsultationSettingsTag();
                $tag->consultationId = $consultation->id;
                $tag->title          = $_POST['tagCreate'];
                $tag->position       = ($maxId + 1);
                $tag->save();
            }

            $consultation->refresh();
        }

        if (isset($_POST["tagSort"]) && is_array($_POST["tagSort"])) {
            foreach ($_POST["tagSort"] as $i => $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne($tagId);
                if ($tag->consultationId == $consultation->id) {
                    $tag->position = $i;
                    $tag->save();
                }
            }
            $consultation->refresh();
        }
    }

    /**
     * @param Site $site
     */
    private function saveSiteSettings(Site $site)
    {
        $ssettings                            = (isset($_POST['siteSettings']) ? $_POST['siteSettings'] : []);
        $siteSettings                         = $site->getSettings();
        $siteSettings->onlyNamespacedAccounts = (isset($ssettings['onlyNamespacedAccounts']) ? 1 : 0);
        $siteSettings->onlyWurzelwerk         = (isset($ssettings['onlyWurzelwerk']) ? 1 : 0);
        $site->setSettings($siteSettings);
        $site->save();

    }

    /**
     * @throws \Exception
     * @throws \app\models\exceptions\FormError
     */
    public function actionConsultationextended()
    {
        $consultation = $this->consultation;

        $this->saveTags($consultation);

        if (isset($_POST['save'])) {
            $consultation->policySupport = $_POST['consultation']['policySupport'];

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
