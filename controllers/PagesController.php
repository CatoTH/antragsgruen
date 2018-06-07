<?php

namespace app\controllers;

use app\components\HTMLTools;
use app\components\MessageSource;
use app\models\db\ConsultationFile;
use app\models\db\ConsultationText;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\NotFound;
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
     * @return string
     * @throws Access
     */
    public function actionSavePage($pageSlug)
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

        $page->text     = HTMLTools::correctHtmlErrors(\Yii::$app->request->post('data'));
        $page->editDate = date('Y-m-d H:i:s');
        $page->save();

        return '1';
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

        $file                 = new ConsultationFile();
        $file->consultationId = $this->consultation->id;
        $file->mimetype       = $mime;
        $file->width          = $width;
        $file->height         = $height;
        $file->filesize       = strlen($content);
        $file->data           = $content;
        $file->dateCreation   = date('Y-m-d H:i:s');
        $file->setFilename($filename);
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

        return $file->data;
    }
}
