<?php

declare(strict_types=1);

namespace app\plugins\generic_sso\controllers;

use app\plugins\generic_sso\Module;
use app\components\UrlHelper;
use app\controllers\Base;
use yii\helpers\Html;

class LoginController extends Base
{
    public $enableCsrfValidation = false;

    // Login is always allowed
    public ?bool $allowNotLoggedIn = true;

    /**
     * Initiate SSO login flow
     */
    public function actionLogin(string $backUrl = ''): void
    {
        if ($backUrl === '') {
            $backUrl = $this->getPostValue('backUrl', UrlHelper::homeUrl());
        }

        // Store backUrl in session for callback
        \Yii::$app->session->set('sso_back_url', $backUrl);

        try {
            $loginProvider = Module::getDedicatedLoginProvider();
            $user = $loginProvider->performLoginAndReturnUser();

            // Retrieve the stored backUrl
            $finalBackUrl = \Yii::$app->session->get('sso_back_url', $backUrl);
            \Yii::$app->session->remove('sso_back_url');

            $this->redirect($finalBackUrl);
        } catch (\Exception $e) {
            error_log('SSO Login Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
    }

    /**
     * OAuth2/OIDC callback endpoint
     */
    public function actionCallback(string $code = '', string $state = '', string $error = ''): void
    {
        if ($error) {
            $errorDescription = $this->getQueryValue('error_description', $error);
            $errorUri = $this->getQueryValue('error_uri', '');

            // Enhanced error logging
            error_log('SSO Callback Error: ' . $error);
            error_log('SSO Error Description: ' . $errorDescription);
            if ($errorUri) {
                error_log('SSO Error URI: ' . $errorUri);
            }
            error_log('SSO Callback URL: ' . \Yii::$app->request->getAbsoluteUrl());

            $this->showErrorpage(
                403,
                'SSO Authentication Error: ' . Html::encode($errorDescription)
            );
            return;
        }

        // Retrieve the stored backUrl
        $backUrl = \Yii::$app->session->get('sso_back_url', UrlHelper::homeUrl());

        try {
            $loginProvider = Module::getDedicatedLoginProvider();
            $user = $loginProvider->performLoginAndReturnUser();

            // Clear the stored backUrl
            \Yii::$app->session->remove('sso_back_url');

            $this->redirect($backUrl);
        } catch (\Exception $e) {
            error_log('SSO Callback Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown') . ':<br> "' . Html::encode($e->getMessage()) . '"'
            );
        }
    }

    /**
     * Get query parameter value
     */
    private function getQueryValue(string $name, $default = null)
    {
        return \Yii::$app->request->get($name, $default);
    }
}
