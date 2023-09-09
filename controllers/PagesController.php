<?php

namespace app\controllers;

use app\models\settings\{Layout, Privileges, AntragsgruenApp};
use app\models\http\{BinaryFileResponse, HtmlErrorResponse, HtmlResponse, JsonResponse, RedirectResponse, ResponseInterface, RestApiResponse};
use app\components\{HTMLTools, Tools, UrlHelper, ZipWriter};
use app\models\db\{ConsultationFile, ConsultationFileGroup, ConsultationText, User};
use app\models\exceptions\{Access, FormError, ResponseException};
use yii\web\{NotFoundHttpException, Response};

class PagesController extends Base
{
    public function actionListPages(): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            return new HtmlErrorResponse(403, 'No permissions to edit this page');
        }

        if ($this->isPostSet('create')) {
            try {
                $url = $this->getHttpRequest()->post('url');
                if (trim($url) === '' || preg_match('/[^\w_\-,.äöüß]/siu', $url)) {
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

                return new RedirectResponse($page->getUrl());
            } catch (FormError $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }

        return new HtmlResponse($this->render('list'));
    }

    protected function getPageForView(string $pageSlug): ?ConsultationText
    {
        $pageData = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);

        // Site-wide pages are always visible. Also, maintenance and legal/privacy pages are always visible.
        // For everything else, check for maintenance mode and login.
        $allowedPages = [ConsultationText::DEFAULT_PAGE_MAINTENANCE, ConsultationText::DEFAULT_PAGE_LEGAL, ConsultationText::DEFAULT_PAGE_PRIVACY];
        if ($pageData->consultation && !in_array($pageSlug, $allowedPages)) {
            if ($this->testMaintenanceMode(null) || $this->testSiteForcedLogin()) {
                throw new ResponseException(new HtmlErrorResponse(404, 'Page not found'));
            }
        }

        return $pageData;
    }

    public function actionShowPage(string $pageSlug): ResponseInterface
    {
        $this->getPageForView($pageSlug); // Assert the page exists

        return $this->renderContentPage($pageSlug);
    }

    public function actionGetRest(string $pageSlug): RestApiResponse
    {
        $pageData = $this->getPageForView($pageSlug);
        if (!$pageData) {
            return $this->returnRestResponseFromException(new \Exception('page not found'));
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

        return new RestApiResponse(200, $data);
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
            if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
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
    public function actionSavePage(string $pageSlug): JsonResponse
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
            if ($newTextId && !preg_match('/[^\w_\-,.äöüß]/siu', $newTextId) && $page->textId !== $newTextId) {
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

        return new JsonResponse($result);
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

    public function actionDeleteFile(): JsonResponse
    {
        $fileId = intval($this->getHttpRequest()->post('id'));
        foreach ($this->consultation->files as $file) {
            if ($file->id === $fileId) {
                $file->delete();
            }
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function actionDeletePage(string $pageSlug): RedirectResponse
    {
        $page = $this->getPageForEdit($pageSlug);

        if ($this->getHttpRequest()->post('delete')) {
            $page->delete();
        }

        $textUrl = UrlHelper::createUrl('pages/list-pages');

        return new RedirectResponse($textUrl);
    }


    public function actionMaintenance(): HtmlResponse
    {
        return $this->renderContentPage('maintenance');
    }

    public function actionLegal(): HtmlResponse
    {
        if (AntragsgruenApp::getInstance()->multisiteMode) {
            $admin      = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null);
            $viewParams = ['pageKey' => 'legal', 'admin' => $admin];

            return new HtmlResponse($this->render('imprint_multisite', $viewParams));
        } else {
            return $this->renderContentPage('legal');
        }
    }

    /**
     * @throws Access
     */
    public function actionUpload(): JsonResponse
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            throw new Access('No permissions to upload files');
        }

        try {
            $user = User::getCurrentUser();
            $file = ConsultationFile::uploadImage($this->consultation, 'upload', $user);

            return new JsonResponse([
                'uploaded' => 1,
                'fileName' => $file->filename,
                'url'      => $file->getUrl(),
            ]);
        } catch (FormError $e) {
            return new JsonResponse([
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
    public function actionBrowseImages(): ResponseInterface
    {
        if (!User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
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

        return new HtmlResponse($this->renderPartial('browse-images', [
            'files'      => $files,
            'msgSuccess' => $msgSuccess,
            'msgError'   => $msgError,
        ]));
    }

    public function actionFile(string $filename): ResponseInterface
    {
        $file = ConsultationFile::findOne([
            'consultationId' => $this->consultation->id,
            'filename'       => $filename,
        ]);
        if (!$file) {
            throw new NotFoundHttpException('file not found', 404);
        }

        return new class($file) implements ResponseInterface {
            private ConsultationFile $file;

            public function __construct(ConsultationFile $file)
            {
                $this->file = $file;
            }

            public function renderYii(Layout $layoutParams, Response $response): ?string
            {
                $response->format = Response::FORMAT_RAW;
                $response->headers->add('Content-Type', $this->file->mimetype);
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
                $response->headers->set('Pragma', 'cache');
                $response->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

                return $this->file->data;
            }
        };
    }

    public function actionCss(): ResponseInterface
    {
        $stylesheetSettings = $this->site->getSettings()->getStylesheet();
        $file               = ConsultationFile::findStylesheetCache($this->site, $stylesheetSettings);
        if ($file) {
            $lines     = explode("\n", $file->data);
            $firstLine = array_shift($lines);
            if ($firstLine === ANTRAGSGRUEN_VERSION) {
                return new BinaryFileResponse(BinaryFileResponse::TYPE_CSS, implode("\n", $lines), false, null, false, 3600 * 24 * 7);
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

        return new BinaryFileResponse(BinaryFileResponse::TYPE_CSS, $toSaveData, false, null, false, 3600 * 24 * 7);
    }

    public function actionDocuments(): ResponseInterface
    {
        $iAmAdmin = User::havePrivilege($this->consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null);
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

        return new HtmlResponse($this->render('documents'));
    }

    public function actionDocumentsZip(string $groupId): BinaryFileResponse
    {
        $zip = new ZipWriter();

        $zipName = 'documents';
        foreach ($this->consultation->fileGroups as $fileGroup) {
            $directory = Tools::sanitizeFilename($fileGroup->title, true);
            if ($fileGroup->id === intval($groupId)) {
                $zipName = Tools::sanitizeFilename($fileGroup->title, false);
            }
            if ($fileGroup->id === intval($groupId) || $groupId === 'all') {
                foreach ($fileGroup->files as $file) {
                    $filename = $directory . '/' . $file->filename;
                    $zip->addFile($filename, $file->data);
                }
            }
        }

        return new BinaryFileResponse(
            BinaryFileResponse::TYPE_ZIP,
            $zip->getContentAndFlush(),
            true,
            $zipName,
            false
        );
    }
}
