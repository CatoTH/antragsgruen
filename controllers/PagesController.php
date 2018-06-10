<?php

namespace app\controllers;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\ConsultationFile;
use app\models\db\ConsultationText;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\settings\AntragsgruenApp;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PagesController extends Base
{
    /**
     * @return string
     * @throws Access
     */
    public function actionListPages()
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to edit this page');
        }

        if (\Yii::$app->request->post('create')) {
            $page                 = new ConsultationText();
            $page->category       = 'pagedata';
            $page->textId         = \Yii::$app->request->post('url');
            $page->title          = \Yii::$app->request->post('title');
            $page->breadcrumb     = \Yii::$app->request->post('title');
            $page->consultationId = $this->consultation->id;
            $page->siteId         = $this->site->id;
            $page->menuPosition   = 1;
            $page->text           = '';
            $page->editDate       = date('Y-m-d H:i:s');
            $page->save();
            return \Yii::$app->response->redirect($page->getUrl());
        }

        return $this->render('list');
    }

    /**
     * @param string $pageSlug
     * @return string
     */
    public function actionShowPage($pageSlug)
    {
        return $this->renderContentPage($pageSlug);
    }

    /**
     * @param string $pageSlug
     * @return ConsultationText|null
     * @throws Access
     */
    protected function getPageForEdit($pageSlug)
    {
        if (\Yii::$app->request->get('pageId')) {
            $page = ConsultationText::findOne(\Yii::$app->request->get('pageId'));
        } else {
            $page = ConsultationText::getPageData($this->site, $this->consultation, $pageSlug);
        }

        if ($page->id) {
            if ($page->siteId && $page->siteId !== $this->site->id) {
                throw new Access('Some inconsistency ocurred (site): ' . $page->siteId . " / " . $this->site->id);
            }
            if ($page->consultationId && $page->consultationId !== $this->consultation->id) {
                throw new Access('Some inconsistency ocurred (consultation)');
            }
        }

        if ($page->siteId) {
            if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
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
     * @param string $pageSlug
     * @return string
     * @throws Access
     */
    public function actionSavePage($pageSlug)
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

        $page->save();

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode([
            'success'          => true,
            'message'          => $message,
            'id'               => $page->id,
            'title'            => $page->title,
            'url'              => $page->textId,
            'allConsultations' => ($page->consultationId === null),
            'redirectTo'       => ($needsReload ? $page->getUrl() : null),
        ]);
    }

    /**
     * @param string $pageSlug
     * @return \yii\console\Response|Response
     * @throws Access
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeletePage($pageSlug)
    {
        $page = $this->getPageForEdit($pageSlug);

        if (\Yii::$app->request->post('delete')) {
            $page->delete();
        }

        $textUrl = UrlHelper::createUrl('pages/list-pages');
        return \Yii::$app->response->redirect($textUrl);
    }


    /**
     * @return string
     */
    public function actionMaintenance()
    {
        return $this->renderContentPage('maintenance');
    }

    /**
     * @return string
     */
    public function actionLegal()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->multisiteMode) {
            $admin      = User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT);
            $viewParams = ['pageKey' => 'legal', 'admin' => $admin];
            return $this->render('imprint_multisite', $viewParams);
        } else {
            return $this->renderContentPage('legal');
        }
    }

    /**
     * @return string
     * @throws Access
     */
    public function actionUpload()
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to upload files');
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $width    = null;
        $height   = null;
        $mime     = null;
        $filename = null;
        $content  = null;
        if (isset($_FILES['upload']) && is_uploaded_file($_FILES['upload']['tmp_name'])) {
            $content = file_get_contents($_FILES['upload']['tmp_name']);
            $info    = getimagesizefromstring($content);
            if ($info && in_array($info['mime'], ['image/png', 'image/jpeg', 'image/gif'])) {
                $mime     = $info['mime'];
                $width    = $info[0];
                $height   = $info[1];
                $filename = $_FILES['upload']['name'];
            } else {
                return json_encode([
                    'uploaded' => 0,
                    'error'    => [
                        'message' => 'Not a valid image file'
                    ],
                ]);
            }
        } else {
            return json_encode([
                'uploaded' => 0,
                'error'    => [
                    'message' => 'No image data uploaded'
                ],
            ]);
        }

        $existingFile = ConsultationFile::findFileByContent($this->consultation, $content);
        if ($existingFile) {
            return json_encode([
                'uploaded' => 1,
                'fileName' => $existingFile->filename,
                'url'      => $existingFile->getUrl(),
            ]);
        }

        $file                 = new ConsultationFile();
        $file->consultationId = $this->consultation->id;
        $file->mimetype       = $mime;
        $file->width          = $width;
        $file->height         = $height;
        $file->dateCreation   = date('Y-m-d H:i:s');
        $file->setFilename($filename);
        $file->setData($content);
        if (!$file->save()) {
            return json_encode([
                'uploaded' => 0,
                'error'    => [
                    'message' => json_encode($file->getErrors())
                ],
            ]);
        }

        return json_encode([
            'uploaded' => 1,
            'fileName' => $filename,
            'url'      => $file->getUrl(),
        ]);
    }

    /**
     * @return string
     * @throws Access
     * @throws \Throwable
     */
    public function actionBrowseImages()
    {
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
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

        $files = $this->consultation->files;
        return $this->renderPartial('browse-images', [
            'files'      => $files,
            'msgSuccess' => $msgSuccess,
            'msgError'   => $msgError,
        ]);
    }

    /**
     * @param string $filename
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionFile($filename)
    {
        $file = ConsultationFile::findOne([
            'consultationId' => $this->consultation->id,
            'filename'       => $filename,
        ]);
        if (!$file) {
            throw new NotFoundHttpException('file not found', 404);
        }

        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', $file->mimetype);
        \yii::$app->response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600 * 24 * 7));
        \yii::$app->response->headers->set('Pragma', 'cache');
        \yii::$app->response->headers->set('Cache-Control', 'public, max-age=' . (3600 * 24 * 7));

        return $file->data;
    }
}
