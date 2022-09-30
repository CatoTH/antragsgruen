<?php

namespace app\controllers;

use app\components\{HTMLTools, Tools, UrlHelper, ZipWriter};
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

            return $this->getHttpResponse();
        }

        if ($this->getHttpRequest()->post('create')) {
            try {
                $url = $this->getHttpRequest()->post('url');
                if (trim($url) === '' || preg_match('/[^\w_\-,\.äöüß]/siu', $url)) {
                    throw new FormError('Invalid character in the URL');
                }
                $page = new ConsultationText();
                $page->category = ConsultationText::DEFAULT_CATEGORY;
                $page->textId = $url;
                $page->title = $this->getHttpRequest()->post('title');
                $page->breadcrumb = $this->getHttpRequest()->post('title');
                $page->consultationId = $this->consultation->id;
                $page->siteId = $this->site->id;
                $page->menuPosition = ($this->isPostSet('inMenu') ? 1 : null);
                $page->text = '';
                $page->editDate = date('Y-m-d H:i:s');
                $page->save();

                return $this->getHttpResponse()->redirect($page->getUrl());
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }

        $this->getHttpResponse()->data = $this->render('list');
        return $this->getHttpResponse();
    }

    protected function getPageForView(string $pageSlug): ?ConsultationText
    {
        $pageData = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);

        // Site-wide pages are always visible. Also, maintenance and legal/privacy pages are always visible.
        // For everything else, check for maintenance mode and login.
        $allowedPages = [ConsultationText::DEFAULT_PAGE_MAINTENANCE, ConsultationText::DEFAULT_PAGE_LEGAL, ConsultationText::DEFAULT_PAGE_PRIVACY];
        if ($pageData->consultation && !in_array($pageSlug, $allowedPages)) {
            if ($this->testMaintenanceMode() || $this->testSiteForcedLogin()) {
                $this->showErrorpage(404, 'Page not found');
                return null;
            }
        }

        return $pageData;
    }

    public function actionShowPage(string $pageSlug): string
    {
        if (!$this->getPageForView($pageSlug)) {
            return '';
        }

        return $this->renderContentPage($pageSlug);
    }

    public function actionGetRest(string $pageSlug): string
    {
        $pageData = $this->getPageForView($pageSlug);
        if (!$pageData) {
            return '';
        }

        $data = [
            'id' => $pageData->id,
            'in_menu' => $pageData->menuPosition !== null,
            'text_id' => $pageData->textId,
            'title' => $pageData->title,
            'url_json' => $pageData->getJsonUrl(),
            'url_html' => $pageData->getUrl(),
            'html' => $pageData->text,
        ];

        return $this->returnRestResponse(200, json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Access
     */
    protected function getPageForEdit(string $pageSlug): ?ConsultationText
    {
        if ($this->getHttpRequest()->get('pageId')) {
            $page = ConsultationText::findOne($this->getHttpRequest()->get('pageId'));
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

        $page->text     = HTMLTools::correctHtmlErrors($this->getHttpRequest()->post('data'));
        $page->editDate = date('Y-m-d H:i:s');

        if (!in_array($page->textId, array_keys(ConsultationText::getDefaultPages()))) {
            if ($this->getHttpRequest()->post('title')) {
                if ($page->breadcrumb === $page->title) {
                    $page->breadcrumb = $this->getHttpRequest()->post('title');
                }
                $page->title = $this->getHttpRequest()->post('title');
            }
            if ($this->getHttpRequest()->post('inMenu') !== null) {
                $menuPos = ($this->getHttpRequest()->post('inMenu') ? 1 : null);
                if ($menuPos !== $page->menuPosition) {
                    $needsReload = true;
                }
                $page->menuPosition = $menuPos;
            }
            if ($this->getHttpRequest()->post('allConsultations') === '1' && $page->consultationId) {
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
            if ($this->getHttpRequest()->post('allConsultations') === '0' && $page->consultationId === null) {
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
            $newTextId = $this->getHttpRequest()->post('url');
            if ($newTextId && !preg_match('/[^\w_\-,\.äöüß]/siu', $newTextId) && $page->textId !== $newTextId) {
                $page->textId = $newTextId;
                $needsReload  = true;
            }
        }
        if ($page->textId === ConsultationText::DEFAULT_PAGE_HELP) {
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

        $downloadableResult = $this->handleDownloadableFiles($this->getHttpRequest()->post());
        $result             = array_merge($result, $downloadableResult);

        $page->save();

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

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
                $post['uploadDownloadableTitle'],
                null
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
        $fileId = intval($this->getHttpRequest()->post('id'));
        foreach ($this->consultation->files as $file) {
            if ($file->id === $fileId) {
                $file->delete();
            }
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        return json_encode([
            'success' => true,
        ]);
    }

    /**
     * @return Response
     * @throws Access
     */
    public function actionDeletePage(string $pageSlug)
    {
        $page = $this->getPageForEdit($pageSlug);

        if ($this->getHttpRequest()->post('delete')) {
            $page->delete();
        }

        $textUrl = UrlHelper::createUrl('pages/list-pages');

        return $this->getHttpResponse()->redirect($textUrl);
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

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

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

        if ($this->getHttpRequest()->post('delete') !== null) {
            try {
                $file = ConsultationFile::findOne([
                    'consultationId' => $this->consultation->id,
                    'id'             => $this->getHttpRequest()->post('id'),
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

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', $file->mimetype);
        $this->getHttpResponse()->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
        $this->getHttpResponse()->headers->set('Pragma', 'cache');
        $this->getHttpResponse()->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

        return $file->data;
    }

    public function actionCss(): string
    {
        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'text/css');
        $this->getHttpResponse()->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
        $this->getHttpResponse()->headers->set('Pragma', 'cache');
        $this->getHttpResponse()->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

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
                ConsultationFile::createDownloadableFile(
                    $this->consultation,
                    User::getCurrentUser(),
                    (string)file_get_contents($_FILES['uploadedFile']['tmp_name']),
                    $_FILES['uploadedFile']['name'],
                    $this->getPostValue('fileTitle'),
                    $group
                );

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

        if ($iAmAdmin && $this->isPostSet('sortDocuments') && $this->isPostSet('document')) {
            $idPosition = [];
            foreach ($this->getPostValue('document') as $pos => $documentId) {
                $idPosition[(int)$documentId] = $pos;
            }

            foreach ($this->consultation->fileGroups as $fileGroup) {
                if (isset($idPosition[$fileGroup->id])) {
                    $fileGroup->position = $idPosition[$fileGroup->id];
                    $fileGroup->save();
                }
            }
        }

        return $this->render('documents');
    }

    public function actionDocumentsZip(string $groupId): string
    {
        $zip = new ZipWriter();

        foreach ($this->consultation->fileGroups as $fileGroup) {
            $directory = Tools::sanitizeFilename($fileGroup->title, true);
            if ($fileGroup->id === intval($groupId) || $groupId === 'all') {
                foreach ($fileGroup->files as $file) {
                    $filename = $directory . '/' . $file->filename;
                    $zip->addFile($filename, $file->data);
                }
            }
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/zip');
        $this->getHttpResponse()->headers->add('Cache-Control', 'max-age=0');

        return $zip->getContentAndFlush();
    }
}
