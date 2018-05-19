<?php

namespace app\controllers;

use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\settings\AntragsgruenApp;

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
        if (!User::havePrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to edit this page');
        }
        if (MessageSource::savePageData($this->consultation, $pageSlug, \Yii::$app->request->post('data'))) {
            return '1';
        } else {
            return '0';
        }
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
            $saveUrl    = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => 'legal']);
            $viewParams = ['pageKey' => 'legal', 'admin' => $admin, 'saveUrl' => $saveUrl];
            return $this->render('imprint_multisite', $viewParams);
        } else {
            return $this->renderContentPage('legal');
        }
    }
}
