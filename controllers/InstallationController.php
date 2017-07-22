<?php

namespace app\controllers;

use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\forms\AntragsgruenInitDb;
use app\models\forms\AntragsgruenInitSite;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;

class InstallationController extends Base
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['index', 'db-test'])) {
            // No cookieValidationKey is set in the beginning
            \Yii::$app->request->enableCookieValidation = false;
            return parent::beforeAction($action);
        }

        $currentHost = parse_url(\Yii::$app->request->getAbsoluteUrl(), PHP_URL_HOST);
        $managerHost = parse_url($this->getParams()->domainPlain, PHP_URL_HOST);
        if ($currentHost != $managerHost) {
            return $this->redirect($this->getParams()->domainPlain, 301);
        }

        return parent::beforeAction($action);
    }


    /**
     * @param AntragsgruenInitDb $dbForm
     * @param string $delInstallFileCmd
     * @param string $makeEditabeCommand
     * @param string $configDir
     * @param boolean $editable
     * @return string
     */
    private function initDb($dbForm, $delInstallFileCmd, $makeEditabeCommand, $configDir, $editable)
    {
        if (PHP_VERSION_ID < 50600) {
            $phpVersionWarning = str_replace('%VERSION%', phpversion(), \Yii::t('manager', 'err_php_version'));
        } else {
            $phpVersionWarning = null;
        }

        return $this->render('init_db', [
            'form'                 => $dbForm,
            'installFileDeletable' => is_writable($configDir),
            'delInstallFileCmd'    => $delInstallFileCmd,
            'editable'             => $editable,
            'makeEditabeCommand'   => $makeEditabeCommand,
            'phpVersionWarning'    => $phpVersionWarning,
        ]);
    }

    /**
     * @param string $installFile
     * @param string $delInstallFileCmd
     * @param string $configDir
     * @return string
     */
    private function createSite($installFile, $delInstallFileCmd, $configDir)
    {
        $configFile = $configDir . DIRECTORY_SEPARATOR . 'config.json';
        $siteForm   = new AntragsgruenInitSite($configFile);

        if ($this->isPostSet('create')) {
            try {
                $post = \Yii::$app->request->post();
                $siteForm->setAttributes($post['SiteCreateForm']);
                $siteForm->prettyUrls = isset($post['SiteCreateForm']['prettyUrls']);

                $siteForm->saveConfig();

                $admin = User::findOne($siteForm->readConfigFromFile()->adminUserIds[0]);
                $siteForm->create($admin);

                Yii::$app->user->login($admin, $this->getParams()->autoLoginDuration);

                $consultationUrl = UrlHelper::createUrl('consultation/index');
                $consultationUrl = UrlHelper::absolutizeLink($consultationUrl);
                $consultationUrl = str_replace('consultation/index', '', $consultationUrl);

                unlink($installFile);
                return $this->render('done', [
                    'installFileDeletable' => is_writable($configDir),
                    'delInstallFileCmd'    => $delInstallFileCmd,
                    'consultationUrl'      => $consultationUrl,
                ]);
            } catch (\Exception $e) {
                \yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        if (!$this->getParams()->multisiteMode) {
            $siteForm->openNow = true;
        }

        return $this->render('create_site', [
            'form'                 => $siteForm,
            'installFileDeletable' => is_writable($configDir),
            'delInstallFileCmd'    => $delInstallFileCmd,
        ]);
    }

    /**
     * @param string $language
     * @return string
     */
    public function actionIndex($language = '')
    {
        $configDir   = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        $installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
        $configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';

        if (!file_exists($installFile)) {
            $msg = \Yii::t('manager', 'already_created_reinit') . '<br><br>';
            $url = Url::toRoute('manager/siteconfig');
            $msg .= Html::a(\Yii::t('manager', 'created_goon_std_config'), $url, ['class' => 'btn btn-primary']);
            $msg = str_replace('%FILE%', Html::encode($installFile), $msg);
            return $this->showErrorpage(403, $msg);
        }

        if (file_exists($configFile)) {
            $editable = is_writable($configFile);
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $myUsername         = posix_getpwuid(posix_geteuid());
                $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configFile . "\n";
                $makeEditabeCommand .= 'sudo chmod u+rwx ' . $configFile . "\n";
            } else {
                $makeEditabeCommand = 'not available';
            }
        } else {
            $editable = is_writable($configDir);
            if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                $myUsername         = posix_getpwuid(posix_geteuid());
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
            \Yii::$app->language = $language;
        }


        $post = \Yii::$app->request->post();
        if ($this->isPostSet('saveDb')) {
            $dbForm->setAttributes($post);

            if ($dbForm->verifyDBConnection(false)) {
                $dbForm->saveConfig();

                if ($dbForm->sqlCreateTables && $dbForm->verifyDBConnection(false) && !$dbForm->tablesAreCreated()) {
                    $dbForm->createTables();
                    \yii::$app->session->setFlash('success', \Yii::t('manager', 'msg_site_created'));
                } else {
                    \yii::$app->session->setFlash('success', \Yii::t('manager', 'msg_config_saved'));
                }

                $dbForm->overwriteYiiConnection();

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

    /**
     * @return string
     * @throws Internal
     */
    public function actionDbTest()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $configDir   = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';
        $installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
        $configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';

        if (!file_exists($installFile)) {
            throw new Internal('Installation mode not activated');
        }

        $post = \Yii::$app->request->post();
        $form = new AntragsgruenInitDb($configFile);
        $form->setAttributes($post);
        if (isset($post['sqlPassword']) && $post['sqlPassword'] != '') {
            $form->sqlPassword = $post['sqlPassword'];
        } elseif (isset($post['sqlPasswordNone'])) {
            $form->sqlPassword = '';
        }

        try {
            $success = $form->verifyDBConnection(true);
            return json_encode([
                'success'        => $success,
                'alreadyCreated' => $form->tablesAreCreated(),
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success'        => false,
                'error'          => $e->getMessage(),
                'alreadyCreated' => null,
            ]);
        }
    }
}
