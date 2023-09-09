<?php

namespace app\plugins\green_manager\controllers;

use app\components\RequestContext;
use app\components\yii\MessageSource;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\http\HtmlResponse;
use app\models\http\JsonResponse;
use app\models\http\ResponseInterface;
use app\models\db\{Consultation, ConsultationText, Site, User};
use app\models\exceptions\{FormError, LoginInvalidUser};
use app\models\forms\{LoginUsernamePasswordForm, SiteCreateForm};
use app\plugins\green_manager\Module;
use yii\helpers\Html;
use yii\web\Response;

class ManagerController extends Base
{
    public function actionIndex(): HtmlResponse
    {
        $this->layout = '@app/views/layouts/column1';
        return new HtmlResponse($this->render('index'));
    }

    public function actionCheckSubdomain(string $test): JsonResponse
    {
        $available = Site::isSubdomainAvailable($test);
        return new JsonResponse([
            'available' => $available,
            'subdomain' => $test,
        ]);
    }

    protected function eligibleToCreateUser(): ?User
    {
        if (!User::getCurrentUser()) {
            return null;
        }

        $user = User::getCurrentUser();
        if ($user->status === User::STATUS_CONFIRMED) {
            return $user;
        } else {
            return null;
        }
    }

    protected function createWelcomePage(Consultation $consultation, string $name): void
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

    public function actionCreatesite(): ResponseInterface
    {
        $language = $this->getRequestValue('language');
        if ($language && isset(MessageSource::getBaseLanguages()[$language])) {
            \Yii::$app->language = $language;
        }

        $model  = new SiteCreateForm();
        $errors = [];

        $post = $this->getHttpRequest()->post();
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
                        RequestContext::getUser()->login($user, $this->getParams()->autoLoginDuration);
                    } catch (LoginInvalidUser $e) {
                        $user = $userForm->doCreateAccount(null);
                        RequestContext::getUser()->login($user, $this->getParams()->autoLoginDuration);
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

                    return new HtmlResponse($this->render('@app/plugins/green_manager/views/manager/created', ['form' => $model]));
                } else {
                    throw new FormError($model->getErrors());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return new HtmlResponse($this->render(
            '@app/plugins/green_manager/views/manager/createsite',
            [
                'model'  => $model,
                'errors' => $errors
            ]
        ));
    }

    public function actionHelp(): HtmlResponse
    {
        return new HtmlResponse($this->render('help'));
    }

    public function actionFreeHosting(): HtmlResponse
    {
        return new HtmlResponse($this->render('free_hosting_faq'));
    }

    public function actionLegal(): HtmlResponse
    {
        return $this->renderContentPage('legal');
    }

    public function actionPrivacy(): HtmlResponse
    {
        return $this->renderContentPage('privacy');
    }
}
