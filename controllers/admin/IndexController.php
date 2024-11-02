<?php

namespace app\controllers\admin;

use app\components\updater\UpdateChecker;
use app\models\api\SpeechQueue as SpeechQueueApi;
use app\models\settings\{Privileges, AntragsgruenApp, Stylesheet, Consultation as ConsultationSettings};
use app\models\http\{BinaryFileResponse, HtmlErrorResponse, HtmlResponse, RedirectResponse, ResponseInterface};
use app\components\{ConsultationAccessPassword, HTMLTools, IMotionStatusFilter, LiveTools, Tools, UrlHelper};
use app\models\db\{Consultation, ConsultationFile, ConsultationSettingsTag, ConsultationText, ISupporter, Site, SpeechQueue, User};
use app\models\exceptions\FormError;
use app\models\forms\{AntragsgruenUpdateModeForm, ConsultationCreateForm};

class IndexController extends AdminBase
{
    public const REQUIRED_PRIVILEGES = [
        Privileges::PRIVILEGE_CONSULTATION_SETTINGS,
        Privileges::PRIVILEGE_SITE_ADMIN,
    ];

    public function actionIndex(): HtmlResponse
    {
        if ($this->isPostSet('flushCaches') && User::currentUserIsSuperuser()) {
            $this->consultation->flushCacheWithChildren(null);
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'index_flushed_cached'));
        }

        if ($this->isPostSet('delSite')) {
            $this->site->setDeleted();
            return new HtmlResponse($this->render('site_deleted'));
        }

        return new HtmlResponse($this->render('index', [
            'site'         => $this->site,
            'consultation' => $this->consultation
        ]));
    }

    public function actionConsultation(): HtmlResponse
    {
        $model = $this->consultation;

        $locale = Tools::getCurrentDateLocale();

        if ($this->isPostSet('save')) {
            $this->saveTags($model);
            $post = $this->getHttpRequest()->post();

            $data = $post['consultation'];
            $model->setAttributes($data);

            $settingsInput = $post['settings'] ?? [];
            $settings = $model->getSettings();
            $settings->saveConsultationForm($settingsInput, $post['settingsFields']);

            if ($model->havePrivilege(Privileges::PRIVILEGE_SITE_ADMIN, null)) {
                if ($this->isPostSet('pwdProtected') && $this->isPostSet('consultationPassword')) {
                    if (trim($post['consultationPassword'])) {
                        $pwdTools = new ConsultationAccessPassword($model);
                        $pwd = password_hash(trim($post['consultationPassword']), PASSWORD_DEFAULT);
                        if ($pwd) {
                            $settings->accessPwd = $pwd;
                            if ($post['otherConsultations'] === '1') {
                                $pwdTools->setPwdForOtherConsultations($settings->accessPwd);
                            }
                        }
                    }
                } else {
                    $settings->accessPwd = null;
                }
            }

            $model->setSettings($settings);

            if (preg_match('/^[\w_-]+$/i', $data['urlPath']) && !in_array(trim($data['urlPath']), Consultation::BLOCKED_URL_PATHS, true)) {
                $model->urlPath = $data['urlPath'];
            } else {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'con_url_path_err'));
            }

            if ($model->save()) {
                if ($model->site->currentConsultationId === $model->id) {
                    $model->site->status = ($settings->maintenanceMode ? Site::STATUS_INACTIVE : Site::STATUS_ACTIVE);
                }

                if ($model->havePrivilege(Privileges::PRIVILEGE_SITE_ADMIN, null)) {
                    $settings = $model->site->getSettings();

                    $settings->loginMethods = [];
                    // Hard-coded login types
                    foreach ($post['login'] ?? [] as $loginIds) {
                        if (is_numeric($loginIds)) {
                            $settings->loginMethods[] = intval($loginIds);
                        }
                    }
                    // Plugin-provided login types
                    foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                        $loginType = $plugin::getDedicatedLoginProvider();
                        if ($loginType && isset($post['login']) && in_array($loginType->getId(), $post['login'])) {
                            $settings->loginMethods[] = $loginType->getId();
                        }
                    }

                    // Prevent locking out myself
                    if (User::getCurrentUser()->getAuthType() === \app\models\settings\Site::LOGIN_STD) {
                        $settings->loginMethods[] = \app\models\settings\Site::LOGIN_STD;
                    }
                    if (User::getCurrentUser()->getAuthType() === \app\models\settings\Site::LOGIN_EXTERNAL) {
                        $settings->loginMethods[] = \app\models\settings\Site::LOGIN_EXTERNAL;
                    }

                    $settingsInput = $post['siteSettings'] ?? [];
                    $settings->saveForm($settingsInput, $post['siteSettingsFields'] ?? []);

                    $model->site->setSettings($settings);
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
                $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                $this->getHttpSession()->setFlash('error', Tools::formatModelValidationErrors($model->getErrors()));
            }
        }

        return new HtmlResponse($this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]));
    }

    public function actionAppearance(): HtmlResponse
    {
        $consultation = $this->consultation;

        if ($this->isPostSet('save')) {
            $post = $this->getHttpRequest()->post();

            $settingsInput = $post['settings'] ?? [];
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

            if ($settingsInput['hasSpeechLists'] ?? false) {
                $subqueues = [];
                if (isset($post['hasMultipleSpeechLists']) && isset($post['multipleSpeechListNames'])) {
                    foreach ($post['multipleSpeechListNames'] as $name) {
                        if (trim($name) !== '') {
                            $subqueues[] = trim($name);
                        }
                    }
                    if (count($subqueues) === 1) {
                        $subqueues = [];
                    }
                }

                foreach ($this->consultation->speechQueues as $speechQueue) {
                    $speechQueue->setSubqueueConfiguration($subqueues);
                }

                $settings->speechListSubqueues = $subqueues;
            }

            if ($settingsInput['showResolutionsCombined'] ?? false) {
                $settings->startLayoutResolutions = ConsultationSettings::START_LAYOUT_RESOLUTIONS_ABOVE;
            } elseif (intval($settingsInput['showResolutionsSeparateMode'] ?? 0) === ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT) {
                $settings->startLayoutResolutions = ConsultationSettings::START_LAYOUT_RESOLUTIONS_DEFAULT;
            } else {
                $settings->startLayoutResolutions = ConsultationSettings::START_LAYOUT_RESOLUTIONS_SEPARATE;
            }

            $settings->saveConsultationForm($settingsInput, $post['settingsFields']);

            if (isset($post['consultationLogo']) && $post['consultationLogo']) {
                $settings->logoUrl = $post['consultationLogo'];
            } elseif (isset($_FILES['newLogo']) && $_FILES['newLogo']['tmp_name']) {
                try {
                    $user              = User::getCurrentUser();
                    $file              = ConsultationFile::uploadImage($this->consultation, 'newLogo', $user);
                    $settings->logoUrl = $file->getUrl();
                } catch (FormError $e) {
                    $this->getHttpSession()->setFlash('error', $e->getMessage());
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

                $settingsInput = $post['siteSettings'] ?? [];
                $siteSettings  = $consultation->site->getSettings();
                $siteSettings->saveForm($settingsInput, $post['siteSettingsFields']);
                $consultation->site->setSettings($siteSettings);
                $consultation->site->save();

                $this->site->getSettings()->siteLayout = $siteSettings->siteLayout;
                $this->layoutParams->setLayout($siteSettings->siteLayout);

                $consultation->refresh();
                foreach ($this->consultation->speechQueues as $speechQueue) {
                    $apiDto = SpeechQueueApi::fromEntity($speechQueue);
                    LiveTools::sendSpeechQueue($this->consultation, $apiDto);
                }

                $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                $this->getHttpSession()->setFlash('error', Tools::formatModelValidationErrors($consultation->getErrors()));
            }
        }

        return new HtmlResponse($this->render('appearance', ['consultation' => $this->consultation]));
    }

    private function saveTags(Consultation $consultation): void
    {
        $newTags = $this->getHttpRequest()->post('tags', []);

        if (isset($newTags['id']) && !isset($newTags['type'])) {
            // Submitted with a page that was loaded before the update; let's better not do anything instead of messing things up
            return;
        }

        $existingTagsById = [];
        foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            $existingTagsById[$tag->id] = $tag;
        }
        foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE) as $tag) {
            $existingTagsById[$tag->id] = $tag;
        }

        $pos = 0;
        for ($i = 0; $i < count($newTags['id'] ?? []); $i++) {
            if (!isset($newTags['title'][$i]) || !isset($newTags['type'][$i])) {
                throw new FormError('Inconsistent input');
            }
            $title = trim($newTags['title'][$i]);
            if ($newTags['id'][$i] === 'NEW') {
                if ($title !== '') {
                    $tag = new ConsultationSettingsTag();
                    $tag->type = intval($newTags['type'][$i]);
                    $tag->title = $title;
                    $tag->consultationId = $this->consultation->id;
                    $tag->position = $pos++;
                    $tag->save();
                }
            } else {
                $tag = $existingTagsById[intval($newTags['id'][$i])];
                unset($existingTagsById[intval($newTags['id'][$i])]);

                $tag->position = $pos++;
                if ($title !== '') {
                    $tag->title = $title;
                }
                $tag->save();
            }
        }

        foreach ($existingTagsById as $tag) {
            $tag->deleteIncludeRelations();
        }

        $consultation->refresh();
    }

    public function actionTranslation(string $category = 'base'): HtmlResponse
    {
        $consultation = $this->consultation;

        if ($this->isPostSet('save') && $this->isPostSet('wordingBase')) {
            $consultation->wordingBase = $this->getHttpRequest()->post('wordingBase');
            $consultation->save();
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        if ($this->isPostSet('save') && $this->isPostSet('string')) {
            foreach ($this->getHttpRequest()->post('string') as $key => $val) {
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
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        return new HtmlResponse($this->render('translation', ['consultation' => $consultation, 'category' => $category]));
    }

    /**
     * @throws \Throwable
     */
    public function actionTranslationMotionType(string $motionTypeId): HtmlResponse
    {
        $consultation = $this->consultation;
        $motionType = $consultation->getMotionType(intval($motionTypeId));

        if ($this->isPostSet('save')) {
            foreach ($this->getHttpRequest()->post('categories', []) as $categoryId => $strings) {
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
                        $text->motionTypeId = intval($motionTypeId);
                        $text->category = $categoryId;
                        $text->textId = $key;
                        $text->text = HTMLTools::cleanHtmlTranslationString($val);
                        $text->editDate = date('Y-m-d H:i:s');
                        $text->save();
                    }
                }
            }
            $motionType->refresh();
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
        }

        return new HtmlResponse($this->render('translation_motion_type', ['motionType' => $motionType]));
    }

    public function actionSiteconsultations(): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_SITE_ADMIN, null)) {
            return new HtmlErrorResponse(403, \Yii::t('admin', 'no_access'));
        }

        $site = $this->site;
        $form = new ConsultationCreateForm($site);
        $form->template = $this->consultation;
        $post = $this->getHttpRequest()->post();

        if ($this->isPostSet('createConsultation')) {
            $newcon = $post['newConsultation'];
            $form->setAttributes($newcon);
            if (preg_match('/^[\w_-]+$/i', $newcon['urlPath']) && !in_array(trim($newcon['urlPath']), Consultation::BLOCKED_URL_PATHS, true)) {
                $form->urlPath = $newcon['urlPath'];
            }
            $form->siteCreateWizard->setAttributes($post['SiteCreateForm']);
            if (isset($newcon['template'])) {
                foreach ($this->site->consultations as $cons) {
                    if ($cons->id === (int)$post['newConsultation']['template']) {
                        $form->template = $cons;
                    }
                }
            }
            try {
                $form->createConsultation();
                $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'cons_new_created'));

                $form = new ConsultationCreateForm($site);
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
            $this->site->refresh();
        }
        if ($this->isPostSet('setStandard')) {
            if (is_array($post['setStandard']) && count($post['setStandard']) == 1) {
                $keys = array_keys($post['setStandard']);
                foreach ($site->consultations as $consultation) {
                    if ($consultation->id === (int)$keys[0]) {
                        $site->currentConsultationId = $consultation->id;
                        if ($consultation->getSettings()->maintenanceMode) {
                            $site->status = Site::STATUS_INACTIVE;
                        } else {
                            $site->status = Site::STATUS_ACTIVE;
                        }
                        $site->save();
                        $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'cons_std_set_done'));
                    }
                }
            }
            $this->site->refresh();
        }
        if ($this->isPostSet('delete') && count($post['delete']) === 1) {
            foreach ($site->consultations as $consultation) {
                $keys = array_keys($post['delete']);
                if ($consultation->id === $keys[0] && $site->currentConsultationId !== $consultation->id) {
                    $consultation->setDeleted();
                    $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'cons_delete_done'));
                    if ($this->consultation->id === $consultation->id) {
                        $fallback = $this->site->currentConsultation->urlPath;

                        return new RedirectResponse(UrlHelper::createUrl(['admin/index/siteconsultations', 'consultationPath' => $fallback]));
                    }
                }
            }
        }

        $consultations = $site->consultations;
        usort($consultations, function (Consultation $con1, Consultation $con2) {
            return -1 * Tools::compareSqlTimes($con1->dateCreation, $con2->dateCreation);
        });

        return new HtmlResponse($this->render('site_consultations', [
            'site'          => $site,
            'consultations' => $consultations,
            'createForm'    => $form,
            'wizardModel'   => $form->siteCreateWizard,
        ]));
    }

    public function actionOpenslidesusers(): BinaryFileResponse
    {
        /** @var ISupporter[] $users */
        $users = [];

        $filter = IMotionStatusFilter::onlyUserVisible($this->consultation, false);

        foreach ($filter->getFilteredConsultationMotions() as $motion) {
            $initiators = $motion->getInitiators();
            $users      = array_merge($users, $initiators);

            foreach ($motion->getVisibleAmendments(false) as $amendment) {
                $initiators = $amendment->getInitiators();
                $users      = array_merge($users, $initiators);
            }
        }

        $csv = $this->renderPartial('openslides2_user_list', [
            'users' => $users,
        ]);
        return new BinaryFileResponse(BinaryFileResponse::TYPE_CSV, $csv, true, 'Participants', false);
    }

    public function actionTheming(string $default = 'layout-classic'): HtmlResponse
    {
        $siteSettings = $this->site->getSettings();
        $stylesheet   = $siteSettings->getStylesheet();

        if ($this->isPostSet('save')) {
            $settings = $this->getHttpRequest()->post('stylesheet', []);
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
                                $this->getHttpSession()->setFlash('error', $e->getMessage());
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
            $resetDefaults = $this->getHttpRequest()->post('defaults', $default);
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

        return new HtmlResponse($this->render('theming', ['stylesheet' => $stylesheet, 'default' => $default]));
    }

    public function actionFiles(): HtmlResponse
    {
        $msgSuccess = '';
        $msgError   = '';

        if ($this->getHttpRequest()->post('delete') !== null) {
            try {
                $file = ConsultationFile::findOne([
                    'siteId' => $this->consultation->site->id,
                    'id'     => $this->getHttpRequest()->post('id'),
                ]);
                if ($file) {
                    $settings = $this->consultation->getSettings();
                    if ($settings->logoUrl === $file->getUrl()) {
                        $settings->logoUrl = null;
                        $this->consultation->setSettings($settings);
                        $this->consultation->save();
                    }

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
            if ($file1->consultationId === $currentCon && $file2->consultationId !== $currentCon) {
                return -1;
            }
            if ($file1->consultationId !== $currentCon && $file2->consultationId === $currentCon) {
                return 1;
            }
            return Tools::compareSqlTimes($file1->dateCreation, $file2->dateCreation);
        });

        return new HtmlResponse($this->render('uploaded_files', [
            'files'      => $files,
            'msgSuccess' => $msgSuccess,
            'msgError'   => $msgError,
        ]));
    }

    public function actionCheckUpdates(): ResponseInterface
    {
        if (!User::currentUserIsSuperuser()) {
            return new HtmlErrorResponse(403, 'Only admins are allowed to access this page.');
        }
        return new HtmlResponse($this->renderPartial('index_updates'));
    }

    public function actionGotoUpdate(): ResponseInterface
    {
        if (!UpdateChecker::isUpdaterAvailable()) {
            return new HtmlErrorResponse(403, 'The updater can only be used with downloaded packages.');
        }
        if (!User::currentUserIsSuperuser()) {
            return new HtmlErrorResponse(403, 'Only admins are allowed to access this page.');
        }

        $form      = new AntragsgruenUpdateModeForm();
        $updateKey = $form->activateUpdate();

        return new RedirectResponse($this->getParams()->resourceBase . 'update.php?set_key=' . $updateKey);
    }
}
