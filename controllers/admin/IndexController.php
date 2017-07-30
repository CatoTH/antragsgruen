<?php

namespace app\controllers\admin;

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\ConsultationText;
use app\models\db\ISupporter;
use app\models\AdminTodoItem;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\forms\ConsultationCreateForm;
use yii\web\Response;

class IndexController extends AdminBase
{
    use SiteAccessTrait;

    /**
     * @return string
     */
    public function actionIndex()
    {
        if ($this->isPostSet('flushCaches') && User::currentUserIsSuperuser()) {
            $this->consultation->flushCacheWithChildren();
            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'index_flushed_cached'));
        }

        if ($this->isPostSet('delSite')) {
            $this->site->setDeleted();
            return $this->render('site_deleted', []);
        }

        return $this->render(
            'index',
            [
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

        if ($this->isPostSet('save')) {
            $this->saveTags($model);
            $post = \Yii::$app->request->post();

            $data = $post['consultation'];
            $model->setAttributes($data);

            $settingsInput = (isset($post['settings']) ? $post['settings'] : []);
            $settings      = $model->getSettings();
            $settings->saveForm($settingsInput, $post['settingsFields']);
            $model->setSettings($settings);

            if ($model->save()) {
                $settingsInput = (isset($post['siteSettings']) ? $post['siteSettings'] : []);
                $siteSettings  = $model->site->getSettings();
                $siteSettings->saveForm($settingsInput, $post['siteSettingsFields']);
                $model->site->setSettings($siteSettings);
                if ($model->site->currentConsultationId == $model->id) {
                    $model->site->status = ($settings->maintenanceMode ? Site::STATUS_INACTIVE : Site::STATUS_ACTIVE);
                }
                $model->site->save();

                if (!$model->getSettings()->adminsMayEdit) {
                    foreach ($model->motions as $motion) {
                        $motion->setTextFixedIfNecessary();
                        foreach ($motion->amendments as $amend) {
                            $amend->setTextFixedIfNecessary();
                        }
                    }
                }

                $this->site->getSettings()->siteLayout = $siteSettings->siteLayout;
                $this->layoutParams->setLayout($siteSettings->siteLayout);

                $model->flushCacheWithChildren();
                \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                \yii::$app->session->setFlash('error', print_r($model->getErrors(), true));
            }
        }

        return $this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]);
    }

    /**
     * @return string
     */
    public function actionTodo()
    {
        $todo = AdminTodoItem::getConsultationTodos($this->consultation);

        return $this->render('todo', ['todo' => $todo]);
    }

    /**
     * @param Consultation $consultation
     */
    private function saveTags(Consultation $consultation)
    {
        if (!$this->isPostSet('tags')) {
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
        $newTags   = json_decode(\Yii::$app->request->post('tags'), true);
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
     * @param string $category
     * @return string
     */
    public function actionTranslation($category = 'base')
    {
        $consultation = $this->consultation;

        if ($this->isPostSet('save') && $this->isPostSet('wordingBase')) {
            $consultation->wordingBase = \Yii::$app->request->post('wordingBase');
            $consultation->save();
            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        if ($this->isPostSet('save') && $this->isPostSet('string')) {
            foreach (\Yii::$app->request->post('string') as $key => $val) {
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
            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        return $this->render('translation', ['consultation' => $consultation, 'category' => $category]);
    }

    /**
     * @return string
     */
    public function actionSiteconsultations()
    {
        $site = $this->site;

        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SITE_ADMIN)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return false;
        }

        $form           = new ConsultationCreateForm($site);
        $form->template = $this->consultation;
        $post           = \Yii::$app->request->post();

        if ($this->isPostSet('createConsultation')) {
            $newcon = $post['newConsultation'];
            $form->setAttributes($newcon, true);
            $form->siteCreateWizard->setAttributes($post['SiteCreateForm']);
            if (isset($newcon['template'])) {
                foreach ($this->site->consultations as $cons) {
                    if ($cons->id == $post['newConsultation']['template']) {
                        $form->template = $cons;
                    }
                }
            }
            try {
                $form->createConsultation();
                \yii::$app->session->setFlash('success', \Yii::t('admin', 'cons_new_created'));

                $form = new ConsultationCreateForm($site);
            } catch (FormError $e) {
                \yii::$app->session->setFlash('error', $e->getMessage());
            }
            $this->site->refresh();
        }
        if ($this->isPostSet('setStandard')) {
            if (is_array($post['setStandard']) && count($post['setStandard']) == 1) {
                $keys = array_keys($post['setStandard']);
                foreach ($site->consultations as $consultation) {
                    if ($consultation->id == $keys[0]) {
                        $site->currentConsultationId = $consultation->id;
                        if ($consultation->getSettings()->maintenanceMode) {
                            $site->status = Site::STATUS_INACTIVE;
                        } else {
                            $site->status = Site::STATUS_ACTIVE;
                        }
                        $site->save();
                        \yii::$app->session->setFlash('success', \Yii::t('admin', 'cons_std_set_done'));
                    }
                }
            }
            $this->site->refresh();
        }
        if ($this->isPostSet('delete') && count($post['delete']) == 1) {
            foreach ($site->consultations as $consultation) {
                $keys = array_keys($post['delete']);
                if ($consultation->id == $keys[0] && $site->currentConsultationId != $consultation->id) {
                    $consultation->setDeleted();
                    \yii::$app->session->setFlash('success', \Yii::t('admin', 'cons_delete_done'));
                    if ($this->consultation->id == $consultation->id) {
                        $fallback = $this->site->currentConsultation->urlPath;
                        
                        $url = UrlHelper::createUrl(['admin/index/siteconsultations', 'consultationPath' => $fallback]);
                        return $this->redirect($url);
                    }
                }
            }
        }

        return $this->render('site_consultations', [
            'site'        => $site,
            'createForm'  => $form,
            'wizardModel' => $form->siteCreateWizard,
        ]);
    }

    /**
     * @param int $version
     * @return string
     */
    public function actionOpenslidesusers($version = 1)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'text/csv');
        \yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=Participants.csv');
        \yii::$app->response->headers->add('Cache-Control', 'max-age=0');

        /** @var ISupporter[] $users */
        $users = [];

        foreach ($this->consultation->getVisibleMotions(false) as $motion) {
            $initiators = $motion->getInitiators();
            $users      = array_merge($users, $initiators);

            foreach ($motion->getVisibleAmendments(false) as $amendment) {
                $initiators = $amendment->getInitiators();
                $users      = array_merge($users, $initiators);
            }
        }

        if ($version == 2) {
            return $this->renderPartial('openslides2_user_list', [
                'users' => $users,
            ]);
        } else {
            return $this->renderPartial('openslides1_user_list', [
                'users' => $users,
            ]);
        }
    }
}
