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
     *
     * @return bool
     * @throws \yii\web\BadRequestHttpException
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
            // Donwloading files is done by curl, not by chrome/firefox.
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

    protected function activateFunctions(): void
    {
        if (!User::havePrivilege($this->consultation, [User::PRIVILEGE_CONSULTATION_SETTINGS, User::PRIVILEGE_SITE_ADMIN])) {
            return;
        }

        if (\Yii::$app->request->get('activate') === 'procedure') {
            foreach ($this->consultation->motionTypes as $motionType) {
                $settings                       = $motionType->getSettingsObj();
                $settings->hasProposedProcedure = true;
                $motionType->setSettingsObj($settings);
                $motionType->save();
            }

            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'list_functions_activated'));
        }
        if (\Yii::$app->request->get('activate') === 'responsibilities') {
            foreach ($this->consultation->motionTypes as $motionType) {
                $settings                      = $motionType->getSettingsObj();
                $settings->hasResponsibilities = true;
                $motionType->setSettingsObj($settings);
                $motionType->save();
            }

            \Yii::$app->session->setFlash('success', \Yii::t('admin', 'list_functions_activated'));
        }
    }
}
