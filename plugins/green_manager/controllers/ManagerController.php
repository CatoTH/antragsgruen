<?php

namespace app\plugins\green_manager\controllers;

use app\components\yii\MessageSource;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\{Consultation, ConsultationText, Site, User};
use app\models\exceptions\{FormError, LoginInvalidUser};
use app\models\forms\{LoginUsernamePasswordForm, SiteCreateForm};
use app\plugins\green_manager\Module;
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
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

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
        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl('/green_manager/manager/index'));
            \Yii::$app->end();
        }
    }

    /**
     * @param Consultation $consultation
     * @param string $name
     * @throws FormError
     */
    protected function createWelcomePage(Consultation $consultation, $name)
    {
        $welcomeHtml = '<h2>Welcome to ' . Html::encode($name) . '</h2>';
        $welcomeHtml .= '<p>You can now start by creating motions, or adjust some detailed settings. As a admin, ';
        $welcomeHtml .= 'you can edit this text and change it to a proper welcome message for your users ';
        $welcomeHtml .= 'by using the "Edit" button to the upper right.</p>';
        $welcomeHtml .= '<p>If you encounter any problems, please do not hesitate to contact us at ' .
            '<a href="mailto:info@discuss.green">info@discuss.green</a>.</p>';

        $legalText                 = new ConsultationText();
        $legalText->siteId         = $consultation->siteId;
        $legalText->consultationId = $consultation->id;
        $legalText->category       = 'pagedata';
        $legalText->textId         = 'welcome';
        $legalText->text           = $welcomeHtml;
        if (!$legalText->save()) {
            throw new FormError($legalText->getErrors());
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

            try {
                if (User::getCurrentUser()) {
                    $user = User::getCurrentUser();
                } else {
                    $userForm = new LoginUsernamePasswordForm(null);
                    $userForm->setAttributes([
                        'username'        => $post['SiteCreateForm']['user_email'],
                        'password'        => $post['SiteCreateForm']['user_pwd'],
                        'passwordConfirm' => $post['SiteCreateForm']['user_pwd'],
                        'name'            => $post['SiteCreateForm']['user_name'],
                    ]);
                    try {
                        $user = $userForm->checkLogin(null);
                        \Yii::$app->user->login($user, $this->getParams()->autoLoginDuration);
                    } catch (LoginInvalidUser $e) {
                        $user = $userForm->doCreateAccount(null);
                        \Yii::$app->user->login($user, $this->getParams()->autoLoginDuration);
                    }
                }

                $model->setAttributes($post['SiteCreateForm']);
                if ($model->validate()) {
                    $consultation = $model->create($user);

                    $settings             = $consultation->site->getSettings();
                    $settings->siteLayout = Module::overridesDefaultLayout();
                    $consultation->site->setSettings($settings);
                    $consultation->site->save();

                    $this->createWelcomePage($consultation, $post['SiteCreateForm']['title']);

                    return $this->render('@app/plugins/green_manager/views/manager/created', ['form' => $model]);
                } else {
                    throw new FormError($model->getErrors());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $this->render(
            '@app/plugins/green_manager/views/manager/createsite',
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
     */
    public function actionFreeHosting()
    {
        return $this->render('free_hosting_faq');
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
