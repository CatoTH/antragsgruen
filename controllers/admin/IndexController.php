<?php

namespace app\controllers\admin;

use app\models\exceptions\UserEditFailed;
use app\models\settings\AntragsgruenApp;
use app\components\mail\Tools as MailTools;
use app\components\updater\UpdateChecker;
use app\components\{ConsultationAccessPassword, HTMLTools, Tools, UrlHelper, UserGroupAdminMethods};
use app\models\db\{Consultation, ConsultationFile, ConsultationSettingsTag, ConsultationText, ConsultationUserGroup, EMailLog, ISupporter, Site, SpeechQueue, User};
use app\models\AdminTodoItem;
use app\models\exceptions\FormError;
use app\models\forms\{AntragsgruenUpdateModeForm, ConsultationCreateForm};
use app\models\settings\Stylesheet;
use yii\base\ExitException;
use yii\web\Response;

class IndexController extends AdminBase
{
    public static $REQUIRED_PRIVILEGES = [
        ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS,
        ConsultationUserGroup::PRIVILEGE_SITE_ADMIN,
    ];

    /** @var UserGroupAdminMethods */
    private $userGroupAdminMethods;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->userGroupAdminMethods = new UserGroupAdminMethods();
            $this->userGroupAdminMethods->setRequestData($this->consultation, $this->getHttpRequest(), $this->getHttpSession());
        }

        return $result;
    }

    public function actionIndex(): string
    {
        if ($this->isPostSet('flushCaches') && User::currentUserIsSuperuser()) {
            $this->consultation->flushCacheWithChildren(null);
            $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'index_flushed_cached'));
        }

        if ($this->isPostSet('delSite')) {
            $this->site->setDeleted();
            return $this->render('site_deleted');
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

            $settingsInput = $post['settings'] ?? [];
            $settings      = $model->getSettings();
            $settings->saveConsultationForm($settingsInput, $post['settingsFields']);
            $settings->setOrganisationsFromInput($post['organisations'] ?? []);

            if ($model->havePrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
                if ($this->isPostSet('pwdProtected') && $this->isPostSet('consultationPassword')) {
                    if (trim($post['consultationPassword'])) {
                        $pwdTools = new ConsultationAccessPassword($model);
                        $pwd = trim($post['consultationPassword']);
                        $settings->accessPwd = password_hash($pwd, PASSWORD_DEFAULT);
                        if ($post['otherConsultations'] === '1') {
                            $pwdTools->setPwdForOtherConsultations($settings->accessPwd);
                        }
                    }
                } else {
                    $settings->accessPwd = null;
                }
            }

            $model->setSettings($settings);

            if (preg_match('/^[\w_-]+$/i', $data['urlPath']) && trim($data['urlPath']) !== 'rest') {
                $model->urlPath = $data['urlPath'];
            } else {
                $this->getHttpSession()->setFlash('error', \Yii::t('admin', 'con_url_path_err'));
            }

            if ($model->save()) {
                if ($model->site->currentConsultationId === $model->id) {
                    $model->site->status = ($settings->maintenanceMode ? Site::STATUS_INACTIVE : Site::STATUS_ACTIVE);
                }

                if ($model->havePrivilege(ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
                    $settings = $model->site->getSettings();
                    if ($this->isPostSet('login')) {
                        $settings->loginMethods = array_map('IntVal', $post['login']);
                    } else {
                        $settings->loginMethods = [];
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

        return $this->render('consultation_settings', ['consultation' => $this->consultation, 'locale' => $locale]);
    }

    public function actionAppearance(): string
    {
        $consultation = $this->consultation;

        if ($this->isPostSet('save')) {
            $post = \Yii::$app->request->post();

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

                $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
            } else {
                $this->getHttpSession()->setFlash('error', Tools::formatModelValidationErrors($consultation->getErrors()));
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
        $foundTags = [];
        $newTags   = \Yii::$app->request->post('tags', []);
        foreach ($newTags as $pos => $newTag) {
            $tag = $consultation->getExistingTagOrCreate(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC, $newTag, $pos);
            if ($tag->position !== $pos) {
                $tag->position = $pos;
                $tag->save();
            }
            $foundTags[] = $tag->id;
        }

        foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
            if (!in_array($tag->id, $foundTags)) {
                \Yii::$app->db->createCommand('DELETE FROM `motionTag` WHERE `tagId` = ' . intval($tag->id))->execute();
                \Yii::$app->db->createCommand('DELETE FROM `amendmentTag` WHERE `tagId` = ' . intval($tag->id))->execute();
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
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
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
            $this->getHttpSession()->setFlash('success', \Yii::t('base', 'saved'));
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

        return $this->render('translation_motion_type', ['motionType' => $motionType]);
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     * @throws \Yii\base\ExitException
     * @throws \Exception
     */
    public function actionSiteconsultations(): string
    {
        $site = $this->site;

        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_SITE_ADMIN)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return '';
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
                    if ($consultation->id == $keys[0]) {
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
        if ($this->isPostSet('delete') && count($post['delete']) == 1) {
            foreach ($site->consultations as $consultation) {
                $keys = array_keys($post['delete']);
                if ($consultation->id === $keys[0] && $site->currentConsultationId !== $consultation->id) {
                    $consultation->setDeleted();
                    $this->getHttpSession()->setFlash('success', \Yii::t('admin', 'cons_delete_done'));
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
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'text/csv');
        \Yii::$app->response->headers->add('Content-Disposition', 'attachment;filename=Participants.csv');
        \Yii::$app->response->headers->add('Cache-Control', 'max-age=0');

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
            if ($file1->consultationId === $currentCon && $file2->consultationId !== $currentCon) {
                return -1;
            }
            if ($file1->consultationId !== $currentCon && $file2->consultationId === $currentCon) {
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
        $usersArr = array_map(function (User $user) use ($consultation): array {
            return $user->getUserAdminApiObject($consultation);
        }, $consultation->getUsersInAnyGroup());
        $groupsArr = array_map(function (ConsultationUserGroup $group): array {
            return $group->getUserAdminApiObject();
        }, $consultation->getAllAvailableUserGroups());

        return [
            'users' => $usersArr,
            'groups' => $groupsArr,
        ];
    }

    public function actionUsers(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        if ($this->isPostSet('addUsers')) {
            if (trim(\Yii::$app->request->post('emailAddresses', '')) !== '') {
                $this->userGroupAdminMethods->addUsersByEmail();
            }
            if (trim(\Yii::$app->request->post('samlWW', '')) !== '' && AntragsgruenApp::getInstance()->isSamlActive()) {
                $this->userGroupAdminMethods->addUsersBySamlWw();
            }
        }

        if ($this->isPostSet('grantAccess')) {
            $userIds = array_map('intval', \Yii::$app->request->post('userId', []));
            $defaultGroup = $this->userGroupAdminMethods->getDefaultUserGroup();
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (!in_array($screeningUser->userId, $userIds)) {
                    continue;
                }
                $user = $screeningUser->user;
                $user->link('userGroups', $defaultGroup);
                /** @noinspection PhpUnhandledExceptionInspection */
                $screeningUser->delete();

                $consUrl = UrlHelper::createUrl('consultation/index');
                $consUrl = UrlHelper::absolutizeLink($consUrl);
                $emailText = str_replace('%LINK%', $consUrl, \Yii::t('user', 'access_granted_email'));

                MailTools::sendWithLog(
                    EMailLog::TYPE_ACCESS_GRANTED,
                    $this->consultation,
                    $user->email,
                    $user->id,
                    \Yii::t('user', 'acc_grant_email_title'),
                    $emailText
                );
            }
            $this->consultation->refresh();
        }

        if ($this->isPostSet('noAccess')) {
            $userIds = array_map('intval', \Yii::$app->request->post('userId', []));
            foreach ($this->consultation->screeningUsers as $screeningUser) {
                if (in_array($screeningUser->userId, $userIds)) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $screeningUser->delete();
                }
            }
            $this->consultation->refresh();
        }

        return $this->render('users', [
            'widgetData' => $this->getUsersWidgetData($consultation),
            'screening' => $consultation->screeningUsers,
        ]);
    }

    public function actionUsersSave(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $additionalData = [
            'msg_success' => null,
            'msg_error' => null,
        ];
        try {
            switch (\Yii::$app->request->post('op')) {
                case 'save-user-groups':
                    $this->userGroupAdminMethods->setUserGroupsToUser(
                        intval(\Yii::$app->request->post('userId')),
                        array_map('intval', \Yii::$app->request->post('groups', []))
                    );
                    break;
                case 'remove-user':
                    $this->userGroupAdminMethods->removeUser(intval(\Yii::$app->request->post('userId')));
                    break;
                case 'create-user-group':
                    $this->userGroupAdminMethods->createUserGroup(\Yii::$app->request->post('groupName'));
                    break;
                case 'remove-group':
                    $this->userGroupAdminMethods->removeUserGroup(intval(\Yii::$app->request->post('groupId')));
                    break;
            }
        } catch (UserEditFailed $failed) {
            $additionalData['msg_error'] = $failed->getMessage();
        }

        return $this->returnRestResponse(200, json_encode(array_merge(
            $this->getUsersWidgetData($consultation),
            $additionalData
        ), JSON_THROW_ON_ERROR));
    }

    public function actionUsersPoll(): string
    {
        $consultation = $this->getConsultationAndCheckAdminPermission();

        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $responseData = $this->getUsersWidgetData($consultation);
        return $this->returnRestResponse(200, json_encode($responseData, JSON_THROW_ON_ERROR));
    }
}
