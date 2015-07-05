<?php

namespace app\controllers\admin;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\User;

class AdminBase extends Base
{
    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (YII_ENV === 'test' && in_array($action->id, ['excellist', 'odslist'])) {
            // Donwloading files is done by curl, not by phantomjs.
            // Therefore the session is lost when downloading in the test environment
            return true;
        }

        if (\Yii::$app->user->isGuest) {
            $currUrl = \yii::$app->request->url;
            $this->redirect(UrlHelper::createLoginUrl($currUrl));
            return false;
        }

        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_ANY)) {
            $this->showErrorpage(403, 'Kein Zugriff auf diese Seite');
            return false;
        }
        return true;
    }
}
