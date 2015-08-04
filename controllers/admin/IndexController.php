<?php

namespace app\controllers\admin;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\Site;
use app\models\AdminTodoItem;

class IndexController extends AdminBase
{
    use SiteAccessTrait;

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
            $comments = MotionComment::getScreeningComments($this->consultation);
            foreach ($comments as $comment) {
                $description = 'Von: ' . $comment->name;
                $todo[]      = new AdminTodoItem(
                    'motionCommentScreen' . $comment->id,
                    'Zu: ' . $comment->motion->getTitleWithPrefix(),
                    'Kommentar freischalten',
                    $comment->getLink(),
                    $description
                );
            }
            $comments = AmendmentComment::getScreeningComments($this->consultation);
            foreach ($comments as $comment) {
                $description = 'Von: ' . $comment->name;
                $todo[]      = new AdminTodoItem(
                    'amendmentCommentScreen' . $comment->id,
                    'Zu: ' . $comment->amendment->getTitle(),
                    'Kommentar freischalten',
                    $comment->getLink(),
                    $description
                );
            }
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
                $model->flushCacheWithChildren();
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
                        $motion->setTextFixedIfNecessary();
                        foreach ($motion->amendments as $amend) {
                            $amend->setTextFixedIfNecessary();
                        }
                    }
                }

                $consultation->flushCacheWithChildren();
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

    /**
     * @return string
     */
    public function actionSiteconsultations()
    {
        $site = $this->site;

        if (isset($_POST['createConsultation'])) {

        }

        if (isset($_POST['setStandard'])) {
            if (is_array($_POST['setStandard']) && count($_POST['setStandard']) == 1) {
                $keys = array_keys($_POST['setStandard']);
                foreach ($site->consultations as $consultation) {
                    if ($consultation->id == $keys[0]) {
                        $site->currentConsultationId = $consultation->id;
                        $site->save();
                        \yii::$app->session->setFlash('success', 'Gespeichert.');
                    }
                }
            }
        }

        return $this->render('site_consultations', ['site' => $site]);
    }
}
