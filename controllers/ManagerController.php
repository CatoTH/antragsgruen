<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\forms\AntragsgruenInitForm;
use app\models\forms\SiteCreateForm;
use Yii;
use yii\db\Exception;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;

class ManagerController extends Base
{
    /**
     *
     */
    protected function addSidebar()
    {
        $sites = Site::getSidebarSites();

        $html = "<ul class='nav nav-list einsatzorte-list'>";
        $html .= "<li class='nav-header'>Aktuelle Einsatzorte</li>";
        foreach ($sites as $site) {
            $url = UrlHelper::createUrl(['consultation/index', "subdomain" => $site->subdomain]);
            $html .= "<li>" . Html::a($site->title, $url) . "</li>\n";
        }
        $html .= '</ul>';
        $this->layoutParams->menusHtml[] = $html;

    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'column2';

        //$this->performLogin($this->createUrl("manager/index"));
        $this->addSidebar();
        return $this->render('index');
    }

    /**
     * @return null|User
     */
    protected function eligibleToCreateUser()
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }

        /** @var User $user */
        $user = yii::$app->user->identity;

        if (!$user->isEntitledToCreateSites()) {
            return null;
        }

        return $user;
    }

    /**
     * @return User
     */
    protected function requireEligibleToCreateUser()
    {
        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl("manager/index"));
        }
        return $user;
    }


    /**
     * @return string
     */
    public function actionCreatesite()
    {
        $this->requireEligibleToCreateUser();

        $this->layout = 'column2';
        $this->addSidebar();

        $model  = new SiteCreateForm();
        $errors = [];

        if (isset($_POST['create'])) {
            try {
                $model->setAttributes($_POST['SiteCreateForm']);
                if ($model->validate()) {
                    $site = $model->createSiteFromForm(User::getCurrentUser());

                    $login_id   = User::getCurrentUser()->id;
                    $login_code = AntiXSS::createToken($login_id);

                    return $this->render(
                        'created',
                        [
                            'site'       => $site,
                            'login_id'   => $login_id,
                            'login_code' => $login_code,
                        ]
                    );
                } else {
                    foreach ($model->getErrors() as $message) {
                        foreach ($message as $message2) {
                            $errors[] = $message2;
                        }
                    }
                }
            } catch (Exception $e) {
                var_dump($e);
            }
        }

        return $this->render(
            'createsite',
            [
                'model'  => $model,
                'errors' => $errors
            ]
        );

    }

    /**
     * @return string
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

    /**
     * @param string $pageKey
     * @return string
     * @throws Access
     */
    public function actionSavetextajax($pageKey)
    {
        $user = User::getCurrentUser();
        if (!$user || !in_array($user->id, $this->getParams()->adminUserIds)) {
            throw new Access('No permissions to edit this page');
        }
        if (MessageSource::savePageData(null, $pageKey, $_POST['data'])) {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     */
    public function actionSiteconfig()
    {
        $user = User::getCurrentUser();
        if (!$user || !in_array($user->id, $this->getParams()->adminUserIds)) {
            return $this->showErrorpage(403, 'Only admins are allowed to access this page.');
        }

        $configfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
        $config     = $this->getParams();

        if ($config->multisiteMode) {
            return $this->showErrorpage(500, 'This configuration tool can only be used for single-site installations.');
        }

        if (isset($_POST['save'])) {
            $config->resourceBase  = $_POST['resourceBase'];
            $config->baseLanguage  = $_POST['baseLanguage'];
            $config->tmpDir        = $_POST['tmpDir'];
            $config->xelatexPath   = $_POST['xelatexPath'];
            $config->xdvipdfmx     = $_POST['xdvipdfmx'];
            $config->mailFromEmail = $_POST['mailFromEmail'];
            $config->mailFromName  = $_POST['mailFromName'];

            switch ($_POST['mailService']['transport']) {
                case 'sendmail':
                    $config->mailService = ['transport' => 'sendmail'];
                    break;
                case 'mandrill':
                    $config->mailService = [
                        'transport' => 'mandrill',
                        'apiKey'    => $_POST['mailService']['mandrillApiKey'],
                    ];
                    break;
                case 'smtp':
                    $config->mailService = [
                        'transport' => 'smtp',
                        'host'      => $_POST['mailService']['smtpHost'],
                        'port'      => $_POST['mailService']['smtpPort'],
                        'authType'  => $_POST['mailService']['smtpAuthType'],
                    ];
                    if ($_POST['mailService']['smtpAuthType'] != 'none') {
                        $config->mailService['username'] = $_POST['mailService']['smtpUsername'];
                        $config->mailService['password'] = $_POST['mailService']['smtpPassword'];
                    }
                    break;
            }

            $file = fopen($configfile, 'w');
            fwrite($file, $config->toJSON());
            fclose($file);

            \yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        $editable = is_writable($configfile);

        $myUsername = posix_getpwuid(posix_geteuid());
        $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configfile;

        return $this->render('siteconfig', [
            'config'             => $config,
            'editable'           => $editable,
            'makeEditabeCommand' => $makeEditabeCommand,
        ]);
    }

    /**
     */
    public function actionAntragsgrueninit()
    {
        $installFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'INSTALLING';
        if (!file_exists($installFile)) {
            $msg = 'Die Seite wurde bereits konfiguriert.<br>
            Um die Grundinstallation erneut aufzurufen, lege bitte folgende Datei an:<br>
            %FILE%<br><br>';
            $url = Url::toRoute('manager/siteconfig');
            $msg .= Html::a('Weiter zur allgemeinen Konfiguration', $url, ['class' => 'btn btn-primary']);
            $msg = str_replace('%FILE%', Html::encode($installFile), $msg);
            return $this->showErrorpage(403, $msg);
        }

        $form = new AntragsgruenInitForm();

        if (isset($_POST['save'])) {
            $form->setAttributes($_POST);

            // @TODO
        }

        return $this->render('antragsgruen_init', ['form' => $form]);
    }

    /**
     * @return string
     */
    public function actionAntragsgrueninitdbtest()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');

        $form = new AntragsgruenInitForm();
        $form->setAttributes($_POST);

        try {
            $success = $form->verifyDBConnection();
            return json_encode(['success' => $success]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
