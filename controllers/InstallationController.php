<?php

namespace app\controllers;

use app\models\http\{HtmlErrorResponse, HtmlResponse, JsonResponse, ResponseInterface};
use app\components\{yii\MessageSource, RequestContext, UrlHelper};
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\forms\{AntragsgruenInitDb, AntragsgruenInitSite};
use yii\helpers\{Html, Url};

class InstallationController extends Base
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['index', 'db-test'])) {
            // No cookieValidationKey is set in the beginning
            $this->getHttpRequest()->enableCookieValidation = false;
            $this->enableCsrfValidation                 = false;
            return parent::beforeAction($action);
        }

        $currentHost = parse_url($this->getHttpRequest()->getAbsoluteUrl(), PHP_URL_HOST);
        $managerHost = parse_url($this->getParams()->domainPlain, PHP_URL_HOST);
        if ($currentHost !== $managerHost) {
            return $this->redirect($this->getParams()->domainPlain, 301);
        }

        return parent::beforeAction($action);
    }


    private function initDb(AntragsgruenInitDb $dbForm, string $delInstallFileCmd, string $makeEditabeCommand, string $configDir, bool $editable): HtmlResponse
    {
        if (!version_compare(PHP_VERSION, ANTRAGSGRUEN_MIN_PHP_VERSION, '>=')) {
            $phpVersionWarning = str_replace(
                ['%MIN_VERSION%', '%CURR_VERSION%'],
                [ANTRAGSGRUEN_MIN_PHP_VERSION, PHP_VERSION],
                \Yii::t('manager', 'err_php_version')
            );
        } else {
            $phpVersionWarning = null;
        }

        return new HtmlResponse($this->render('init_db', [
            'form'                 => $dbForm,
            'installFileDeletable' => is_writable($configDir),
            'delInstallFileCmd'    => $delInstallFileCmd,
            'editable'             => $editable,
            'makeEditabeCommand'   => $makeEditabeCommand,
            'phpVersionWarning'    => $phpVersionWarning,
        ]));
    }

    private function createSite(string $installFile, string $delInstallFileCmd, string $configDir): HtmlResponse
    {
        $configFile = $configDir . DIRECTORY_SEPARATOR . 'config.json';
        $siteForm   = new AntragsgruenInitSite($configFile);

        if ($this->isPostSet('create')) {
            try {
                $post = $this->getHttpRequest()->post();
                $siteForm->setAttributes($post['SiteCreateForm']);
                $siteForm->prettyUrls = isset($post['SiteCreateForm']['prettyUrls']);

                $siteForm->saveConfig();

                $admin = User::findOne($siteForm->readConfigFromFile()->adminUserIds[0]);
                $siteForm->create($admin);

                RequestContext::getYiiUser()->login($admin, $this->getParams()->autoLoginDuration);

                $consultationUrl = UrlHelper::createUrl('consultation/index');
                $consultationUrl = UrlHelper::absolutizeLink($consultationUrl);
                $consultationUrl = str_replace('consultation/index', '', $consultationUrl);

                unlink($installFile);
                return new HtmlResponse($this->render('done', [
                    'installFileDeletable' => is_writable($configDir),
                    'delInstallFileCmd'    => $delInstallFileCmd,
                    'consultationUrl'      => $consultationUrl,
                ]));
            } catch (\Exception $e) {
                $this->getHttpSession()->setFlash('error', $e->getMessage());
            }
        }
        if (!$this->getParams()->multisiteMode) {
            $siteForm->openNow = true;
        }

        return new HtmlResponse($this->render('create_site', [
            'form'                 => $siteForm,
            'installFileDeletable' => is_writable($configDir),
            'delInstallFileCmd'    => $delInstallFileCmd,
        ]));
    }

    public function actionIndex(string $language = ''): ResponseInterface
    {
        $configDir   = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        $installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
        $configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';

        if (!file_exists($installFile)) {
            $msg = \Yii::t('manager', 'already_created_reinit') . '<br><br>';
            $url = Url::toRoute('manager/siteconfig');
            $msg .= Html::a(\Yii::t('manager', 'created_goon_std_config'), $url, ['class' => 'btn btn-primary']);
            $msg = str_replace('%FILE%', Html::encode($installFile), $msg);
            return new HtmlErrorResponse(403, $msg);
        }

        if (file_exists($configFile)) {
            $editable = is_writable($configFile);
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $myUsername = posix_getpwuid(posix_geteuid());
                if (!$myUsername) {
                    return new HtmlErrorResponse(500, 'Could not determine username');
                }
                $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configFile . "\n";
                $makeEditabeCommand .= 'sudo chmod u+rwx ' . $configFile . "\n";
            } else {
                $makeEditabeCommand = 'not available';
            }
        } else {
            $editable = is_writable($configDir);
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $myUsername = posix_getpwuid(posix_geteuid());
                if (!$myUsername) {
                    return new HtmlErrorResponse(500, 'Could not determine username');
                }
                $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configDir . "\n";
                $makeEditabeCommand .= 'sudo chmod u+rwx ' . $configDir . "\n";
            } else {
                $makeEditabeCommand = 'not available';
            }
        }

        $delInstallFileCmd = 'rm ' . $installFile;

        $dbForm = new AntragsgruenInitDb($configFile);
        if ($language == '') {
            $language = $dbForm->language;
        }
        if (isset(MessageSource::getBaseLanguages()[$language])) {
            $dbForm->language    = $language;
            RequestContext::getWebApplication()->language = $language;
        }


        $post = $this->getHttpRequest()->post();
        if ($this->isPostSet('saveDb')) {
            $dbForm->setAttributes($post);

            if ($dbForm->verifyDBConnection(false)) {
                $dbForm->saveConfig();

                if ($dbForm->sqlCreateTables && $dbForm->verifyDBConnection(false) && !$dbForm->tablesAreCreated()) {
                    $dbForm->createTables();
                    $this->getHttpSession()->setFlash('success', \Yii::t('manager', 'msg_site_created'));
                } else {
                    $this->getHttpSession()->setFlash('success', \Yii::t('manager', 'msg_config_saved'));
                }

                $dbForm->overwriteYiiConnection();
                $dbForm->overwritePrettyUrls();

                if ($dbForm->adminUsername != '' && $dbForm->adminPassword != '') {
                    $dbForm->createOrUpdateAdminAccount();
                    $dbForm->saveConfig();
                }
            }
        }

        if ($dbForm->verifyDBConnection(false) && $dbForm->tablesAreCreated() && $dbForm->hasAdminAccount()) {
            return $this->createSite($installFile, $delInstallFileCmd, $configDir);
        } else {
            return $this->initDb($dbForm, $delInstallFileCmd, $makeEditabeCommand, $configDir, $editable);
        }
    }

    public function actionDbTest(): JsonResponse
    {
        $configDir   = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        $installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
        $configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';

        if (!file_exists($installFile)) {
            throw new Internal('Installation mode not activated');
        }

        $post = $this->getHttpRequest()->post();
        $form = new AntragsgruenInitDb($configFile);
        $form->setAttributes($post);
        if (isset($post['sqlPassword']) && $post['sqlPassword'] != '') {
            $form->sqlPassword = $post['sqlPassword'];
        } elseif (isset($post['sqlPasswordNone'])) {
            $form->sqlPassword = '';
        }

        try {
            $success = $form->verifyDBConnection(true);
            return new JsonResponse([
                'success'        => $success,
                'alreadyCreated' => $form->tablesAreCreated(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success'        => false,
                'error'          => $e->getMessage(),
                'alreadyCreated' => null,
            ]);
        }
    }
}
