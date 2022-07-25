<?php

namespace app\controllers;

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{ConsultationFile, ConsultationFileGroup, ConsultationText, ConsultationUserGroup, User};
use app\models\exceptions\{Access, FormError};
use app\models\settings\AntragsgruenApp;
use yii\web\{NotFoundHttpException, Response};

class PagesController extends Base
{
    public function actionListPages(): Response
    {
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
            $this->showErrorpage(403, 'No permissions to edit this page');

            return \Yii::$app->response;
        }

        if (\Yii::$app->request->post('create')) {
            try {
                $url = \Yii::$app->request->post('url');
                if (trim($url) === '' || preg_match('/[^\w_\-,\.äöüß]/siu', $url)) {
                    throw new FormError('Invalid character in the URL');
                }
                $page                 = new ConsultationText();
                $page->category       = 'pagedata';
                $page->textId         = $url;
                $page->title          = \Yii::$app->request->post('title');
                $page->breadcrumb     = \Yii::$app->request->post('title');
                $page->consultationId = $this->consultation->id;
                $page->siteId         = $this->site->id;
                $page->menuPosition   = 1;
                $page->text           = '';
                $page->editDate       = date('Y-m-d H:i:s');
                $page->save();

                return \Yii::$app->response->redirect($page->getUrl());
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }

        \Yii::$app->response->data = $this->render('list');
        return \Yii::$app->response;
    }

    public function actionShowPage(string $pageSlug): string
    {
        $pageData = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);

        // Site-wide pages are always visible. Also, maintenance and legal/privacy pages are always visible.
        // For everything else, check for mainenance mode and login.
        $allowedPages = [ConsultationText::DEFAULT_PAGE_MAINTENANCE, ConsultationText::DEFAULT_PAGE_LEGAL, ConsultationText::DEFAULT_PAGE_PRIVACY];
        if ($pageData->consultation && !in_array($pageSlug, $allowedPages)) {
            if ($this->testMaintenanceMode() || $this->testSiteForcedLogin()) {
                $this->showErrorpage(404, 'Page not found');
                return '';
            }
        }

        return $this->renderContentPage($pageSlug);
    }

    /**
     * @throws Access
     */
    protected function getPageForEdit(string $pageSlug): ?ConsultationText
    {
        if (\Yii::$app->request->get('pageId')) {
            $page = ConsultationText::findOne(\Yii::$app->request->get('pageId'));
        } else {
            $page = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);
        }

        if ($page->id) {
            if ($page->siteId && $page->siteId !== $this->site->id) {
                throw new Access('Some inconsistency occurred (site): ' . $page->siteId . " / " . $this->site->id);
            }
            if ($page->consultationId && $page->consultationId !== $this->consultation->id) {
                throw new Access('Some inconsistency occurred (consultation)');
            }
        }

        if ($page->siteId) {
            if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
                throw new Access('No permissions to edit this page');
            }
        } else {
            if (!User::currentUserIsSuperuser()) {
                throw new Access('No permissions to edit this page');
            }
        }

        return $page;
    }

    /**
     * @throws Access
     */
    public function actionSavePage(string $pageSlug): string
    {
        $page = $this->getPageForEdit($pageSlug);

        $needsReload = false;
        $message     = '';

        $page->text     = HTMLTools::correctHtmlErrors(\Yii::$app->request->post('data'));
        $page->editDate = date('Y-m-d H:i:s');

        if (!in_array($page->textId, array_keys(ConsultationText::getDefaultPages()))) {
            if (\Yii::$app->request->post('title')) {
                if ($page->breadcrumb === $page->title) {
                    $page->breadcrumb = \Yii::$app->request->post('title');
                }
                $page->title = \Yii::$app->request->post('title');
            }
            if (\Yii::$app->request->post('inMenu') !== null) {
                $menuPos = (\Yii::$app->request->post('inMenu') ? 1 : null);
                if ($menuPos !== $page->menuPosition) {
                    $needsReload = true;
                }
                $page->menuPosition = $menuPos;
            }
            if (\Yii::$app->request->post('allConsultations') === '1' && $page->consultationId) {
                $alreadyCreatedPage = ConsultationText::findOne([
                    'category'       => 'pagedata',
                    'siteId'         => $page->siteId,
                    'consultationId' => null,
                    'textId'         => $page->textId,
                ]);
                if ($alreadyCreatedPage) {
                    $message = 'There already is a site-wide content page with this URL.';
                } else {
                    $page->consultationId = null;
                }
            }
            if (\Yii::$app->request->post('allConsultations') === '0' && $page->consultationId === null) {
                $alreadyCreatedPage = ConsultationText::findOne([
                    'category'       => 'pagedata',
                    'consultationId' => $this->consultation->id,
                    'textId'         => $page->textId,
                ]);
                if ($alreadyCreatedPage) {
                    $message = 'There already is a consultation content page with this URL.';
                } else {
                    $page->consultationId = $this->consultation->id;
                }
            }
            $newTextId = \Yii::$app->request->post('url');
            if ($newTextId && !preg_match('/[^\w_\-,\.äöüß]/siu', $newTextId) && $page->textId !== $newTextId) {
                $page->textId = $newTextId;
                $needsReload  = true;
            }
        }
        if ($page->textId === 'help') {
            $page->menuPosition = 1;
        }

        $result = [
            'success'          => true,
            'message'          => $message,
            'id'               => $page->id,
            'title'            => $page->title,
            'url'              => $page->textId,
            'allConsultations' => ($page->consultationId === null),
            'redirectTo'       => ($needsReload ? $page->getUrl() : null),
        ];

        $downloadableResult = $this->handleDownloadableFiles(\Yii::$app->request->post());
        $result             = array_merge($result, $downloadableResult);

        $page->save();

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode($result);
    }

    private function handleDownloadableFiles(array $post): array
    {
        $result = [];
        if (isset($post['uploadDownloadableFile']) && strlen($post['uploadDownloadableFile']) > 0) {
            $file                   = ConsultationFile::createDownloadableFile(
                $this->consultation,
                User::getCurrentUser(),
                base64_decode($post['uploadDownloadableFile']),
                $post['uploadDownloadableFilename'],
                $post['uploadDownloadableTitle']
            );
            $result['uploadedFile'] = [
                'title' => $file->title,
                'url'   => $file->getUrl(),
                'id'    => $file->id,
            ];
        }

        return $result;
    }

    public function actionDeleteFile(): string
    {
        $fileId = intval(\Yii::$app->request->post('id'));
        foreach ($this->consultation->files as $file) {
            if ($file->id === $fileId) {
                $file->delete();
            }
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode([
            'success' => true,
        ]);
    }

    /**
     * @return \yii\console\Response|Response
     * @throws Access
     */
    public function actionDeletePage(string $pageSlug)
    {
        $page = $this->getPageForEdit($pageSlug);

        if (\Yii::$app->request->post('delete')) {
            $page->delete();
        }

        $textUrl = UrlHelper::createUrl('pages/list-pages');

        return \Yii::$app->response->redirect($textUrl);
    }


    public function actionMaintenance(): string
    {
        return $this->renderContentPage('maintenance');
    }

    public function actionLegal(): string
    {
        if (AntragsgruenApp::getInstance()->multisiteMode) {
            $admin      = User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT);
            $viewParams = ['pageKey' => 'legal', 'admin' => $admin];

            return $this->render('imprint_multisite', $viewParams);
        } else {
            return $this->renderContentPage('legal');
        }
    }

    /**
     * @throws Access
     */
    public function actionUpload(): string
    {
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to upload files');
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        try {
            $user = User::getCurrentUser();
            $file = ConsultationFile::uploadImage($this->consultation, 'upload', $user);

            return json_encode([
                'uploaded' => 1,
                'fileName' => $file->filename,
                'url'      => $file->getUrl(),
            ]);
        } catch (FormError $e) {
            return json_encode([
                'uploaded' => 0,
                'error'    => [
                    'message' => $e->getMessage()
                ],
            ]);
        }
    }

    /**
     * @throws Access
     */
    public function actionBrowseImages(): string
    {
        if (!User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to upload files');
        }

        $msgSuccess = '';
        $msgError   = '';

        if (\Yii::$app->request->post('delete') !== null) {
            try {
                $file = ConsultationFile::findOne([
                    'consultationId' => $this->consultation->id,
                    'id'             => \Yii::$app->request->post('id'),
                ]);
                if ($file) {
                    $file->delete();
                }

                $this->consultation->refresh();

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

        return $this->renderPartial('browse-images', [
            'files'      => $files,
            'msgSuccess' => $msgSuccess,
            'msgError'   => $msgError,
        ]);
    }

    public function actionFile(string $filename): string
    {
        $file = ConsultationFile::findOne([
            'consultationId' => $this->consultation->id,
            'filename'       => $filename,
        ]);
        if (!$file) {
            throw new NotFoundHttpException('file not found', 404);
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', $file->mimetype);
        \Yii::$app->response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
        \Yii::$app->response->headers->set('Pragma', 'cache');
        \Yii::$app->response->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

        return $file->data;
    }

    public function actionCss(): string
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'text/css');
        \Yii::$app->response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
        \Yii::$app->response->headers->set('Pragma', 'cache');
        \Yii::$app->response->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

        $stylesheetSettings = $this->site->getSettings()->getStylesheet();
        $file               = ConsultationFile::findStylesheetCache($this->site, $stylesheetSettings);
        if ($file) {
            $lines     = explode("\n", $file->data);
            $firstLine = array_shift($lines);
            if ($firstLine === ANTRAGSGRUEN_VERSION) {
                return implode("\n", $lines);
            } else {
                $file->delete();
            }
        }

        $data       = $this->renderPartial('css', [
            'stylesheetSettings' => $stylesheetSettings,
            'format' => \ScssPhp\ScssPhp\Formatter\Compressed::class,
        ]);
        $toSaveData = ANTRAGSGRUEN_VERSION . "\n" . $data;
        ConsultationFile::createStylesheetCache($this->site, $stylesheetSettings, $toSaveData);

        return $data;
    }

    public function actionDocuments(): string
    {
        $iAmAdmin = User::havePrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT);
        if ($iAmAdmin && $this->isPostSet('createGroup') && $this->getPostValue('name')) {
            $group = new ConsultationFileGroup();
            $group->title = trim($this->getPostValue('name'));
            $group->consultationId = $this->consultation->id;
            $group->parentGroupId = null;
            $group->position = ConsultationFileGroup::getNextAvailablePosition($this->consultation);
            $group->save();

            $this->getHttpSession()->setFlash('success', \Yii::t('pages', 'documents_group_added'));
            $this->consultation->refresh();
        }

        if ($iAmAdmin && $this->isPostSet('deleteGroup') && $this->getPostValue('groupId') > 0) {
            $found = false;
            foreach ($this->consultation->fileGroups as $fileGroup) {
                if ($fileGroup->id === intval($this->getPostValue('groupId'))) {
                    foreach ($fileGroup->files as $file) {
                        $file->delete();
                    }
                    $fileGroup->delete();
                    $found = true;
                }
            }
            if ($found) {
                $this->getHttpSession()->setFlash('success', \Yii::t('pages', 'documents_group_deleted'));
                $this->consultation->refresh();
            }
        }

        if ($iAmAdmin && $this->isPostSet('uploadFile') && $this->getPostValue('groupId') > 0) {
            $group = null;
            foreach ($this->consultation->fileGroups as $fileGroup) {
                if ($fileGroup->id === (int)$this->getPostValue('groupId')) {
                    $group = $fileGroup;
                }
            }

            if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === 0 && $_FILES['uploadedFile']['size'] > 0) {
                $file = ConsultationFile::createDownloadableFile(
                    $this->consultation,
                    User::getCurrentUser(),
                    (string)file_get_contents($_FILES['uploadedFile']['tmp_name']),
                    $_FILES['uploadedFile']['name'],
                    $this->getPostValue('fileTitle')
                );

                $group->link('files', $file);

                $this->getHttpSession()->setFlash('success', \Yii::t('pages', 'documents_uploaded_file'));
                $this->consultation->refresh();
            }
        }

        if ($iAmAdmin && $this->isPostSet('deleteFile')) {
            $toDeleteIds = array_map('intval', array_keys($this->getPostValue('deleteFile', [])));
            $found = false;
            foreach ($this->consultation->files as $file) {
                if (in_array($file->id, $toDeleteIds)) {
                    $file->delete();
                    $found = true;
                }
            }
            if ($found) {
                $this->getHttpSession()->setFlash('success', \Yii::t('pages', 'documents_file_deleted'));
                $this->consultation->refresh();
            }
        }

        return $this->render('documents');
    }
}
