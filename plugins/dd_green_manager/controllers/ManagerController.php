<?php

namespace app\plugins\dd_green_manager\controllers;

use app\components\HTMLTools;
use app\components\MessageSource;
use app\components\Tools;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\Site;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\db\User;
use app\models\exceptions\DB;
use app\models\exceptions\FormError;
use app\models\forms\LoginUsernamePasswordForm;
use app\models\forms\SiteCreateForm;
use app\plugins\dd_green_manager\Module;
use yii\helpers\Html;
use yii\web\Response;

class ManagerController extends Base
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = '@app/views/layouts/column1';
        return $this->render('index');
    }

    /**
     * @param string $test
     * @return string
     */
    public function actionCheckSubdomain($test)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $available = Site::isSubdomainAvailable($test);
        return json_encode([
            'available' => $available,
            'subdomain' => $test,
        ]);
    }

    /**
     * @return null|User
     */
    protected function eligibleToCreateUser()
    {
        if (\Yii::$app->user->isGuest) {
            return null;
        }

        /** @var User $user */
        $user = \Yii::$app->user->identity;
        if ($user->status === User::STATUS_CONFIRMED) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     */
    protected function requireEligibleToCreateUser()
    {
        if ($this->getParams()->mode == 'sandbox') {
            // In sandbox mode, everyone is allowed to create a site
            return;
        }

        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl('/dd_green_manager/manager/index'));
            \Yii::$app->end();
        }
    }

    /**
     * @return string
     */
    public function actionCreatesite()
    {
        $language = $this->getRequestValue('language');
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }

        $model  = new SiteCreateForm();
        $errors = [];

        $post = \Yii::$app->request->post();
        if (isset($post['create'])) {
            $post['SiteCreateForm']['contact'] = $post['SiteCreateForm']['organization'];

            if (User::getCurrentUser()) {
                $user = User::getCurrentUser();
            } else {
                $userForm = new LoginUsernamePasswordForm();
                $userForm->setAttributes([
                    'createAccount'   => true,
                    'username'        => $post['SiteCreateForm']['user_email'],
                    'password'        => $post['SiteCreateForm']['user_pwd'],
                    'passwordConfirm' => $post['SiteCreateForm']['user_pwd'],
                    'name'            => $post['SiteCreateForm']['user_name'],
                ]);
                $user = $userForm->getOrCreateUser(null);
                \Yii::$app->user->login($user, $this->getParams()->autoLoginDuration);
            }

            try {
                $model->setAttributes($post['SiteCreateForm']);
                if ($model->validate()) {
                    $consultation = $model->create($user);

                    $settings             = $consultation->site->getSettings();
                    $settings->siteLayout = Module::overridesDefaultLayout();
                    $consultation->site->setSettings($settings);
                    $consultation->site->save();

                    return $this->render('@app/plugins/dd_green_manager/views/manager/created', ['form' => $model]);
                } else {
                    throw new FormError($model->getErrors());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $this->render(
            '@app/plugins/dd_green_manager/views/manager/createsite',
            [
                'model'  => $model,
                'errors' => $errors
            ]
        );
    }

    /**
     * @return string
     */
    public function actionHelp()
    {
        return $this->render('help');
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function actionLegal()
    {
        return $this->renderContentPage('legal');
    }

    /**
     * @return string
     */
    public function actionPrivacy()
    {
        return $this->renderContentPage('privacy');
    }


}
