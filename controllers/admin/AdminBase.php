<?php

namespace app\controllers\admin;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\User;

class AdminBase extends Base
{
    public static $REQUIRED_PRIVILEGES = [
        User::PRIVILEGE_ANY,
    ];

    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (YII_ENV === 'test' && in_array($action->id, [
                'excellist', 'odslist', 'openslides', 'openslidesusers',
                'motion-excellist', 'motion-odslist', 'motion-openslides', 'motion-yopenslidesusers',
            ])) {
            // Donwloading files is done by curl, not by phantomjs.
            // Therefore the session is lost when downloading in the test environment
            return true;
        }

        if (\Yii::$app->user->isGuest) {
            $this->redirect(UrlHelper::createUrl(['user/login', 'backUrl' => $_SERVER['REQUEST_URI']]));
            return false;
        }

        if (!User::havePrivilege($this->consultation, static::$REQUIRED_PRIVILEGES)) {
            $this->showErrorpage(403, \Yii::t('admin', 'no_access'));
            return false;
        }
        return true;
    }
}
