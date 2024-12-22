<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\{BackgroundJobScheduler, RequestContext};
use app\models\db\User;
use app\models\settings\AntragsgruenApp;
use app\models\http\{HtmlErrorResponse, HtmlResponse, ResponseInterface, RestApiResponse};

class ManagerController extends Base
{
    public const VIEW_ID_SITECONFIG = 'siteconfig';
    public const VIEW_ID_HEALTH = 'health';

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (in_array($action->id, [self::VIEW_ID_SITECONFIG, self::VIEW_ID_HEALTH])) {
            // No cookieValidationKey is set in the beginning
            RequestContext::getWebApplication()->request->enableCookieValidation = false;
            return parent::beforeAction($action);
        }

        if (!$this->getParams()->multisiteMode && !in_array($action->id, [self::VIEW_ID_SITECONFIG, self::VIEW_ID_HEALTH])) {
            return false;
        }

        $currentHost = parse_url(RequestContext::getWebApplication()->request->getAbsoluteUrl(), PHP_URL_HOST);
        $managerHost = parse_url($this->getParams()->domainPlain, PHP_URL_HOST);
        if ($currentHost !== $managerHost) {
            return $this->redirect($this->getParams()->domainPlain, 301);
        }

        return parent::beforeAction($action);
    }

    public function actionSiteconfig(): ResponseInterface
    {
        if (!User::currentUserIsSuperuser()) {
            return new HtmlErrorResponse(403, 'Only admins are allowed to access this page.');
        }

        $configfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
        $config     = $this->getParams();

        if ($config->multisiteMode) {
            return new HtmlErrorResponse(500, 'This configuration tool can only be used for single-site installations.');
        }

        $post = RequestContext::getWebApplication()->request->post();
        if (isset($post['save'])) {
            $config->resourceBase          = $post['resourceBase'];
            $config->baseLanguage          = $post['baseLanguage'];
            $config->lualatexPath          = $post['lualatexPath'];
            $config->mailFromEmail         = $post['mailFromEmail'];
            $config->mailFromName          = $post['mailFromName'];
            $config->confirmEmailAddresses = isset($post['confirmEmailAddresses']);

            switch ($post['mailService']['transport']) {
                case 'none':
                    $config->mailService = ['transport' => 'none'];
                    break;
                case 'sendmail':
                    $config->mailService = ['transport' => 'sendmail'];
                    break;
                case 'mandrill':
                    $config->mailService = [
                        'transport' => 'mandrill',
                        'apiKey'    => $post['mailService']['mandrillApiKey'],
                    ];
                    break;
                case 'mailgun':
                    $config->mailService = [
                        'transport' => 'mailgun',
                        'apiKey'    => $post['mailService']['mailgunApiKey'],
                        'domain'    => $post['mailService']['mailgunDomain'],
                    ];
                    break;
                case 'mailjet':
                    $config->mailService = [
                        'transport'        => 'mailjet',
                        'apiKey'           => $post['mailService']['mailjetApiKey'],
                        'mailjetApiSecret' => $post['mailService']['mailjetApiSecret'],
                    ];
                    break;
                case 'smtp':
                    $config->mailService = [
                        'transport'  => 'smtp',
                        'host'       => $post['mailService']['smtpHost'],
                        'port'       => $post['mailService']['smtpPort'],
                        'authType'   => $post['mailService']['smtpAuthType'],
                        'encryption' => (isset($post['mailService']['smtpTls']) ? 'tls' : null),
                    ];
                    if ($post['mailService']['smtpAuthType'] != 'none') {
                        $config->mailService['username'] = $post['mailService']['smtpUsername'];
                        $config->mailService['password'] = $post['mailService']['smtpPassword'];
                    }
                    break;
            }

            /** @var resource $file */
            $file = fopen($configfile, 'w');
            fwrite($file, json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            fclose($file);

            $this->getHttpSession()->setFlash('success', \Yii::t('manager', 'saved'));
        }

        $editable = is_writable($configfile);

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid') && ($myUsername = posix_getpwuid(posix_geteuid()))) {
            $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configfile;
        } else {
            $makeEditabeCommand = 'not available';
        }

        return new HtmlResponse($this->render('siteconfig', [
            'config'             => $config,
            'editable'           => $editable,
            'makeEditabeCommand' => $makeEditabeCommand,
        ]));
    }

    public function actionHealth(): RestApiResponse
    {
        $pwdHash = AntragsgruenApp::getInstance()->healthCheckKey;
        if ($pwdHash === null) {
            return new RestApiResponse(404, ['success' => false, 'error' => 'Health checks not activated']);
        }
        if ($this->getHttpHeader('X-API-Key') === null || !password_verify($this->getHttpHeader('X-API-Key'), $pwdHash)) {
            return new RestApiResponse(401, ['success' => false, 'error' => 'No or invalid X-API-Key given']);
        }

        $backgroundJobs = BackgroundJobScheduler::getDiagnostics();
        $healthy = $backgroundJobs['healthy'] !== false;

        return new RestApiResponse(200, [
            'success' => true,
            'healthy' => $healthy,
            'backgroundJobs' => $backgroundJobs['data'],
        ]);
    }
}
