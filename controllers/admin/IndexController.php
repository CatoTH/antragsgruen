<?php

namespace app\controllers\admin;

use app\components\{HTMLTools, Tools, updater\UpdateChecker, UrlHelper};
use app\models\db\{Consultation, ConsultationFile, ConsultationSettingsTag, ConsultationText, ISupporter, Site, SpeechQueue, User};
use app\models\AdminTodoItem;
use app\models\exceptions\FormError;
use app\models\forms\{AntragsgruenUpdateModeForm, ConsultationCreateForm};
use app\models\settings\Stylesheet;
use yii\web\Response;

class IndexController extends AdminBase
{
    use SiteAccessTrait;

    public static $REQUIRED_PRIVILEGES = [
        User::PRIVILEGE_CONSULTATION_SETTINGS,
        User::PRIVILEGE_SITE_ADMIN,
    ];

    public function actionIndex(): string
    {
        if ($this->isPostSet('flushCaches') && User::currentUserIsSuperuser()) {
            $this->consultation->flushCacheWithChildren(null);
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

    public function actionConsultation(): string
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
            $settings->setOrganisationsFromInput($post['organisations']);

            $model->setSettings($settings);

            if (preg_match('/^[\w_-]+$/i', $data['urlPath']) && trim($data['urlPath']) !== 'rest') {
                $model->urlPath = $data['urlPath'];
            } else {
                \yii::$app->session->setFlash('error', \Yii::t('admin', 'con_url_path_err'));
            }

            if ($model->save()) {
                if ($model->site->currentConsultationId === $model->id) {
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

                $model->flushCacheWithChildren(['lines']);
                \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                \yii::$app->session->setFlash('error', Tools::formatModelValidationErrors($model->getErrors()));
            }
        }

        return $this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]);
    }

    public function actionAppearance(): string
    {
        $consultation = $this->consultation;

        if ($this->isPostSet('save')) {
            $post = \Yii::$app->request->post();

            $settingsInput = (isset($post['settings']) ? $post['settings'] : []);
            $settings      = $consultation->getSettings();

            if (isset($settingsInput['translationService']) && isset($post['translationSpecificService'])) {
                if (in_array($post['translationSpecificService'], ['google', 'bing'])) {
                    $settings->translationService = $post['translationSpecificService'];
                } else {
                    $settings->translationService = null;
                }
            } else {
                $settings->translationService = null;
            }

            if (isset($settingsInput['hasSpeechLists']) && $settingsInput['hasSpeechLists']) {
                if (isset($post['hasMultipleSpeechLists']) && $post['hasMultipleSpeechLists']) {
                    $subqueues = [];
                    if (isset($post['multipleSpeechListNames'])) {
                        foreach ($post['multipleSpeechListNames'] as $name) {
                            if (trim($name) !== '') {
                                $subqueues[] = trim($name);
                            }
                        }
                    }
                } else {
                    $subqueues = [];
                }

                foreach ($this->consultation->speechQueues as $speechQueue) {
                    $speechQueue->setSubqueueConfiguration($subqueues);
                }

                $settings->speechListSubqueues = $subqueues;
            }

            $settings->saveForm($settingsInput, $post['settingsFields']);

            if (isset($post['consultationLogo']) && $post['consultationLogo']) {
                $settings->logoUrl = $post['consultationLogo'];
            } elseif (isset($_FILES['newLogo']) && $_FILES['newLogo']['tmp_name']) {
                try {
                    $user              = User::getCurrentUser();
                    $file              = ConsultationFile::uploadImage($this->consultation, 'newLogo', $user);
                    $settings->logoUrl = $file->getUrl();
                } catch (FormError $e) {
                    \yii::$app->session->setFlash('error', $e->getMessage());
                }
            }
            $consultation->setSettings($settings);

            if ($consultation->save()) {
                if (isset($settingsInput['hasSpeechLists']) && $settingsInput['hasSpeechLists']) {
                    // Creating speech subquees needs to be done after $consultation->setSettings, so that the subqueue configuration is already set
                    if (count($this->consultation->speechQueues) === 0 && isset($post['speechActivateFirstList'])) {
                        $unassignedQueue = SpeechQueue::createWithSubqueues($this->consultation, true);
                        $unassignedQueue->save();
                    }
                }
                
                $settingsInput = (isset($post['siteSettings']) ? $post['siteSettings'] : []);
                $siteSettings  = $consultation->site->getSettings();
                $siteSettings->saveForm($settingsInput, $post['siteSettingsFields']);
                $consultation->site->setSettings($siteSettings);
                $consultation->site->save();

                $this->site->getSettings()->siteLayout = $siteSettings->siteLayout;
                $this->layoutParams->setLayout($siteSettings->siteLayout);

                \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                \yii::$app->session->setFlash('error', Tools::formatModelValidationErrors($consultation->getErrors()));
            }
        }

        return $this->render('appearance', ['consultation' => $this->consultation]);
    }

    public function actionTodo(): string
    {
        $todo = AdminTodoItem::getConsultationTodos($this->consultation);

        return $this->render('todo', ['todo' => $todo]);
    }

    private function saveTags(Consultation $consultation): void
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
                $tag->position = $pos;
                $tag->save();
            }
            $foundTags[] = $tag->id;
        }

        foreach ($consultation->tags as $tag) {
            if (!in_array($tag->id, $foundTags)) {
                \Yii::$app->db->createCommand('DELETE FROM `motionTag` WHERE `tagId` = ' . intval($tag->id))->execute();
                $tag->delete();
            }
        }

        $consultation->refresh();
    }

    public function actionTranslation(string $category = 'base'): string
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
                    if ($text->category === $category && $text->textId === $key) {
                        if ($val === '') {
                            $text->delete();
                        } else {
                            $text->text = HTMLTools::cleanHtmlTranslationString($val);
                            $text->save();
                        }
                        $found = true;
                    }
                }
                if (!$found && $val !== '') {
                    $text                 = new ConsultationText();
                    $text->consultationId = $consultation->id;
                    $text->category       = $category;
                    $text->textId         = $key;
                    $text->text           = HTMLTools::cleanHtmlTranslationString($val);
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
     * @throws \Throwable
     */
    public function actionTranslationMotionType(string $motionTypeId): string
    {
        $consultation = $this->consultation;
        $motionType = $consultation->getMotionType(intval($motionTypeId));

        if ($this->isPostSet('save')) {
            foreach (\Yii::$app->request->post('categories', []) as $categoryId => $strings) {
                foreach ($strings as $key => $val) {
                    $key = urldecode($key);
                    $found = false;
                    foreach ($motionType->consultationTexts as $text) {
                        if ($text->category === $categoryId && $text->textId === $key) {
                            if ($val === '') {
                                $text->delete();
                            } else {
                                $text->text = HTMLTools::cleanHtmlTranslationString($val);
                                $text->save();
                            }
                            $found = true;
                        }
                    }
                    if (!$found && $val !== '') {
                        $text = new ConsultationText();
                        $text->motionTypeId = $motionTypeId;
                        $text->category = $categoryId;
                        $text->textId = $key;
                        $text->text = HTMLTools::cleanHtmlTranslationString($val);
                        $text->editDate = date('Y-m-d H:i:s');
                        $text->save();
                    }
                }
            }
            $motionType->refresh();
            \yii::$app->session->setFlash('success', \Yii::t('base', 'saved'));
        }

        return $this->render('translation_motion_type', ['motionType' => $motionType]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \yii\base\ExitException
     * @throws \Exception
     */
    public function actionSiteconsultations(): string
    {
        $site = $this->site;

        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_SITE_ADMIN)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return false;
        }

        $form           = new ConsultationCreateForm($site);
        $form->template = $this->consultation;
        $post           = \Yii::$app->request->post();

        if ($this->isPostSet('createConsultation')) {
            $newcon = $post['newConsultation'];
            $form->setAttributes($newcon, true);
            if (preg_match('/^[\w_-]+$/i', $newcon['urlPath'])) {
                $form->urlPath = $newcon['urlPath'];
            }
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
                if ($consultation->id === $keys[0] && $site->currentConsultationId !== $consultation->id) {
                    $consultation->setDeleted();
                    \yii::$app->session->setFlash('success', \Yii::t('admin', 'cons_delete_done'));
                    if ($this->consultation->id === $consultation->id) {
                        $fallback = $this->site->currentConsultation->urlPath;

                        $url = UrlHelper::createUrl(['admin/index/siteconsultations', 'consultationPath' => $fallback]);
                        return $this->redirect($url);
                    }
                }
            }
        }

        $consultations = $site->consultations;
        usort($consultations, function (Consultation $con1, Consultation $con2) {
            return -1 * Tools::compareSqlTimes($con1->dateCreation, $con2->dateCreation);
        });

        return $this->render('site_consultations', [
            'site'          => $site,
            'consultations' => $consultations,
            'createForm'    => $form,
            'wizardModel'   => $form->siteCreateWizard,
        ]);
    }

    public function actionOpenslidesusers(): string
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

        return $this->renderPartial('openslides2_user_list', [
            'users' => $users,
        ]);
    }

    public function actionTheming(string $default = 'layout-classic'): string
    {
        $siteSettings = $this->site->getSettings();
        $stylesheet   = $siteSettings->getStylesheet();

        if ($this->isPostSet('save')) {
            $settings = \Yii::$app->request->post('stylesheet', []);
            foreach (Stylesheet::getAllSettings($default) as $key => $setting) {
                switch ($setting['type']) {
                    case Stylesheet::TYPE_CHECKBOX:
                        $stylesheet->$key = isset($settings[$key]);
                        break;
                    case Stylesheet::TYPE_NUMBER:
                    case Stylesheet::TYPE_PIXEL:
                        $stylesheet->$key = IntVal($settings[$key]);
                        break;
                    case Stylesheet::TYPE_COLOR:
                        if (preg_match('/^[a-f0-9]{6}$/siu', $settings[$key])) {
                            $stylesheet->$key = '#' . $settings[$key];
                        }
                        if (preg_match('/^#[a-f0-9]{6}$/siu', $settings[$key])) {
                            $stylesheet->$key = $settings[$key];
                        }
                        break;
                    case Stylesheet::TYPE_FONT:
                        $stylesheet->$key = $settings[$key];
                        break;
                    case Stylesheet::TYPE_IMAGE:
                        if (isset($settings[$key]) && $settings[$key]) {
                            $stylesheet->$key = $settings[$key];
                        } elseif (isset($_FILES['uploaded_' . $key]) && $_FILES['uploaded_' . $key]['tmp_name']) {
                            try {
                                $user = User::getCurrentUser();
                                $file = ConsultationFile::uploadImage($this->consultation, 'uploaded_' . $key, $user);

                                $stylesheet->$key = $file->getUrl();
                            } catch (FormError $e) {
                                \yii::$app->session->setFlash('error', $e->getMessage());
                            }
                        }
                        break;
                }
            }
            $siteSettings->setStylesheet($stylesheet);
            $siteSettings->siteLayout = 'layout-custom-' . $stylesheet->getSettingsHash();
            $this->site->setSettings($siteSettings);
            $this->site->save();

            $this->layoutParams->setLayout($siteSettings->siteLayout);
        }

        if ($this->isPostSet('resetTheme')) {
            $resetDefaults = \Yii::$app->request->post('defaults', $default);
            $data = [];
            foreach (Stylesheet::getAllSettings($resetDefaults) as $key => $setting) {
                $data[$key] = $setting['default'];
            }
            $stylesheet = new Stylesheet($data);
            $siteSettings->setStylesheet($stylesheet);
            $siteSettings->siteLayout = 'layout-custom-' . $stylesheet->getSettingsHash();
            $this->site->setSettings($siteSettings);
            $this->site->save();

            $this->layoutParams->setLayout($siteSettings->siteLayout);
        }

        return $this->render('theming', ['stylesheet' => $stylesheet, 'default' => $default]);
    }

    public function actionFiles(): string
    {
        $msgSuccess = '';
        $msgError   = '';

        if (\Yii::$app->request->post('delete') !== null) {
            try {
                $file = ConsultationFile::findOne([
                    'siteId' => $this->consultation->site->id,
                    'id'     => \Yii::$app->request->post('id'),
                ]);
                if ($file) {
                    $file->delete();
                }

                $this->consultation->refresh();
                $this->site->refresh();

                $msgSuccess = \Yii::t('pages', 'images_deleted');
            } catch (\Exception $e) {
                $msgError = $e->getMessage();
            }
        }

        $files = $this->site->files;
        $files = array_values(array_filter($files, function (ConsultationFile $file) {
            return $file->filename !== 'styles.css';
        }));
        usort($files, function (ConsultationFile $file1, ConsultationFile $file2) {
            $currentCon = $this->consultation->id;
            if ($file1->consultationId === $currentCon && $file1->consultationId !== $currentCon) {
                return -1;
            }
            if ($file1->consultationId !== $currentCon && $file1->consultationId === $currentCon) {
                return 1;
            }
            return Tools::compareSqlTimes($file1->dateCreation, $file2->dateCreation);
        });

        return $this->render('uploaded_files', [
            'files'      => $files,
            'msgSuccess' => $msgSuccess,
            'msgError'   => $msgError,
        ]);
    }

    public function actionCheckUpdates(): string
    {
        if (!User::currentUserIsSuperuser()) {
            $this->showErrorpage(403, 'Only admins are allowed to access this page.');
            return '';
        }
        return $this->renderPartial('index_updates');
    }

    public function actionGotoUpdate(): string
    {
        if (!UpdateChecker::isUpdaterAvailable()) {
            $this->showErrorpage(403, 'The updater can only be used with downloaded packages.');
            return '';
        }
        if (!User::currentUserIsSuperuser()) {
            $this->showErrorpage(403, 'Only admins are allowed to access this page.');
            return '';
        }

        $form      = new AntragsgruenUpdateModeForm();
        $updateKey = $form->activateUpdate();

        return $this->redirect($this->getParams()->resourceBase . 'update.php?set_key=' . $updateKey);
    }
}
