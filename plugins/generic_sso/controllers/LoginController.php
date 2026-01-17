<?php

declare(strict_types=1);

namespace app\plugins\generic_sso\controllers;

use app\plugins\generic_sso\Module;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\settings\Site as SiteSettings;
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
        // Verify login method is enabled
        if (!in_array((int)SiteSettings::LOGIN_EXTERNAL, $this->site->getSettings()->loginMethods)) {
            throw new \Exception('This login method is not enabled');
        }

        if ($backUrl === '') {
            $backUrl = $this->getPostValue('backUrl', UrlHelper::homeUrl());
        }

        // Validate backUrl to prevent open redirect
        $backUrl = $this->validateBackUrl($backUrl);

        // Store backUrl in session for callback
        \Yii::$app->session->set('sso_back_url', $backUrl);

        $this->performLoginAndRedirect($backUrl);
    }

    /**
     * OAuth2/OIDC callback endpoint
     */
    public function actionCallback(string $code = '', string $state = '', string $error = ''): void
    {
        if ($error) {
            $errorDescription = \Yii::$app->request->get('error_description', $error);
            $errorUri = \Yii::$app->request->get('error_uri', '');

            // Log error details
            \Yii::error('SSO Callback Error: ' . $error);
            \Yii::error('SSO Error Description: ' . $errorDescription);
            if ($errorUri) {
                \Yii::error('SSO Error URI: ' . $errorUri);
            }

            $this->showErrorpage(
                403,
                'SSO Authentication Error: ' . Html::encode($errorDescription)
            );
            return;
        }

        // Retrieve the stored backUrl
        $backUrl = \Yii::$app->session->get('sso_back_url', UrlHelper::homeUrl());

        $this->performLoginAndRedirect($backUrl);
    }

    /**
     * Validate backUrl to prevent open redirect vulnerabilities
     */
    private function validateBackUrl(string $backUrl): string
    {
        // If empty, use home URL
        if (empty($backUrl)) {
            return UrlHelper::homeUrl();
        }

        // Only allow relative URLs (starting with /)
        if (strpos($backUrl, '/') === 0 && strpos($backUrl, '//') !== 0) {
            return $backUrl;
        }

        // Check if it's an absolute URL to the same host
        $currentHost = \Yii::$app->request->getHostInfo();
        if (strpos($backUrl, $currentHost) === 0) {
            return $backUrl;
        }

        // Default to home URL for any other case
        \Yii::warning('Invalid backUrl rejected: ' . $backUrl);
        return UrlHelper::homeUrl();
    }

    /**
     * Perform SSO login and redirect to back URL
     */
    private function performLoginAndRedirect(string $defaultBackUrl): void
    {
        try {
            $loginProvider = Module::getDedicatedLoginProvider();
            $loginProvider->performLoginAndReturnUser();

            // Retrieve and clear the stored backUrl
            $finalBackUrl = \Yii::$app->session->get('sso_back_url', $defaultBackUrl);
            \Yii::$app->session->remove('sso_back_url');

            // Validate again before redirect
            $finalBackUrl = $this->validateBackUrl($finalBackUrl);

            $this->redirect($finalBackUrl);
        } catch (\Exception $e) {
            \Yii::error('SSO Login Error: ' . $e->getMessage());
            \Yii::error('Stack trace: ' . $e->getTraceAsString());

            $this->showErrorpage(
                500,
                \Yii::t('user', 'err_unknown')
            );
        }
    }
}
