<?php

namespace app\controllers;

use app\models\db\User;

class ManagerController extends Base
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['siteconfig'])) {
            // No cookieValidationKey is set in the beginning
            \Yii::$app->request->enableCookieValidation = false;
            return parent::beforeAction($action);
        }

        if (!$this->getParams()->multisiteMode && !in_array($action->id, ['siteconfig', 'userlist'])) {
            return false;
        }

        $currentHost = parse_url(\Yii::$app->request->getAbsoluteUrl(), PHP_URL_HOST);
        $managerHost = parse_url($this->getParams()->domainPlain, PHP_URL_HOST);
        if ($currentHost != $managerHost) {
            return $this->redirect($this->getParams()->domainPlain, 301);
        }

        return parent::beforeAction($action);
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionSiteconfig()
    {
        if (!User::currentUserIsSuperuser()) {
            return $this->showErrorpage(403, 'Only admins are allowed to access this page.');
        }

        $configfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
        $config     = $this->getParams();

        if ($config->multisiteMode) {
            return $this->showErrorpage(500, 'This configuration tool can only be used for single-site installations.');
        }

        $post = \Yii::$app->request->post();
        if (isset($post['save'])) {
            $config->resourceBase          = $post['resourceBase'];
            $config->baseLanguage          = $post['baseLanguage'];
            $config->tmpDir                = $post['tmpDir'];
            $config->xelatexPath           = $post['xelatexPath'];
            $config->xdvipdfmx             = $post['xdvipdfmx'];
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
                        'transport' => 'smtp',
                        'host'      => $post['mailService']['smtpHost'],
                        'port'      => $post['mailService']['smtpPort'],
                        'authType'  => $post['mailService']['smtpAuthType'],
                    ];
                    if ($post['mailService']['smtpAuthType'] != 'none') {
                        $config->mailService['username'] = $post['mailService']['smtpUsername'];
                        $config->mailService['password'] = $post['mailService']['smtpPassword'];
                    }
                    break;
            }

            $file = fopen($configfile, 'w');
            fwrite($file, $config->toJSON());
            fclose($file);

            \yii::$app->session->setFlash('success', \Yii::t('manager', 'saved'));
        }

        $editable = is_writable($configfile);

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $myUsername         = posix_getpwuid(posix_geteuid());
            $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configfile;
        } else {
            $makeEditabeCommand = 'not available';
        }

        return $this->render('siteconfig', [
            'config'             => $config,
            'editable'           => $editable,
            'makeEditabeCommand' => $makeEditabeCommand,
        ]);
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionUserlist()
    {
        if (!User::currentUserIsSuperuser()) {
            return $this->showErrorpage(403, 'Only admins are allowed to access this page.');
        }

        $users = User::find()->orderBy('dateCreation DESC')->all();

        return $this->render('userlist', ['users' => $users]);
    }
}
