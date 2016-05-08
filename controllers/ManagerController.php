<?php

namespace app\controllers;

use app\components\AntiXSS;
use app\components\HTMLTools;
use app\components\MessageSource;
use app\components\UrlHelper;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\Access;
use app\models\exceptions\Internal;
use app\models\forms\AntragsgruenInitForm;
use app\models\forms\SiteCreateForm;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;

class ManagerController extends Base
{

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['antragsgrueninit', 'antragsgrueninitdbtest'])) {
            // No cookieValidationKey is set in the beginning
            \Yii::$app->request->enableCookieValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     *
     */
    protected function addSidebar()
    {
        $sites        = Site::getSidebarSites();
        $sitesCurrent = [];
        $sitesOld     = [];
        foreach ($sites as $site) {
            $url      = UrlHelper::createUrl(['consultation/index', 'subdomain' => $site->subdomain]);
            $siteData = [
                'title'        => $site->title,
                'organization' => $site->organization,
                'url'          => $url,
            ];
            if ($site->status == Site::STATUS_ACTIVE) {
                $sitesCurrent[] = $siteData;
            } else {
                $sitesOld[] = $siteData;
            }
        }

        $sitesCurrent = $this->getParams()->getBehaviorClass()->getManagerCurrentSidebarSites($sitesCurrent);
        $html         = '<ul class="nav nav-list current-uses-list">';
        $html .= '<li class="nav-header">' . \Yii::t('manager', 'sidebar_curr_uses') . '</li>';
        foreach ($sitesCurrent as $data) {
            $html .= '<li>';
            if ($data['organization'] != '') {
                $html .= '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
            }
            $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
        }
        $html .= '</ul>';
        $this->layoutParams->menusHtml[] = $html;


        $sitesOld = $this->getParams()->getBehaviorClass()->getManagerOldSidebarSites($sitesOld);
        $html     = '<ul class="nav nav-list current-uses-list old-uses-list">';
        $html .= '<li class="nav-header">' . \Yii::t('manager', 'sidebar_old_uses') . '</li>';
        $html .= '<li class="shower"><a href="#" onClick="$(\'.old-uses-list .hidden\').removeClass(\'hidden\');
            $(\'.old-uses-list .shower\').addClass(\'hidden\'); return false;" style="font-style: italic;">' .
            \Yii::t('manager', 'sidebar_old_uses_show') . '</a></li>';

        foreach ($sitesOld as $data) {
            $html .= '<li class="hidden">';
            if ($data['organization'] != '') {
                $html .= '<span class="orga">' . HTMLTools::encodeAddShy($data['organization']) . '</span>';
            }
            $html .= Html::a(HTMLTools::encodeAddShy($data['title']), $data['url']) . '</li>' . "\n";
        }
        $html .= '</ul>';
        $this->layoutParams->menusHtml[] = $html;

    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        if (!$this->wordpressMode) {
            $this->layout = 'column2';
        }

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
     * @return User|null
     */
    protected function requireEligibleToCreateUser()
    {
        $user = $this->eligibleToCreateUser();
        if (!$user) {
            $this->redirect(UrlHelper::createUrl('manager/index'));
            return null;
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

        $post = \Yii::$app->request->post();
        if (isset($post['create'])) {
            try {
                $model->setAttributes($post['SiteCreateForm']);
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
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
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
        if (!User::currentUserIsSuperuser()) {
            throw new Access('No permissions to edit this page');
        }
        if (MessageSource::savePageData(null, $pageKey, \Yii::$app->request->post('data'))) {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     * @return string
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

        $myUsername         = posix_getpwuid(posix_geteuid());
        $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configfile;

        return $this->render('siteconfig', [
            'config'             => $config,
            'editable'           => $editable,
            'makeEditabeCommand' => $makeEditabeCommand,
        ]);
    }

    /**
     * @return string
     */
    public function actionAntragsgrueninit()
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

        $myUsername = posix_getpwuid(posix_geteuid());
        if (file_exists($configFile)) {
            $editable           = is_writable($configFile);
            $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configFile;
        } else {
            $editable           = is_writable($configDir);
            $makeEditabeCommand = 'sudo chown ' . $myUsername['name'] . ' ' . $configDir;
        }

        $form = new AntragsgruenInitForm($configFile);

        $baseUrl = parse_url($form->siteUrl);
        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '' &&
            isset($baseUrl['host']) && $baseUrl['host'] != $_SERVER['HTTP_HOST']
        ) {
            return $this->redirect($form->siteUrl);
        }

        $post = \Yii::$app->request->post();

        if ($this->isPostSet('finishInit')) {
            unlink($installFile);
            return $this->render('antragsgruen_init_done');
        }

        if ($this->isPostSet('save')) {
            $form->setAttributes($post);
            if (isset($post['sqlPassword']) && $post['sqlPassword'] != '') {
                $form->sqlPassword = $post['sqlPassword'];
            } elseif (isset($post['sqlPasswordNone'])) {
                $form->sqlPassword = '';
            }
            $form->sqlCreateTables = isset($post['sqlCreateTables']);
            $form->prettyUrls      = isset($post['prettyUrls']);

            if ($editable) {
                $form->saveConfig();
            }

            if ($form->sqlCreateTables && $form->verifyDBConnection(false) && !$form->tablesAreCreated()) {
                $form->createTables();
                \yii::$app->session->setFlash('success', \Yii::t('manager', 'msg_site_created'));
            } else {
                \yii::$app->session->setFlash('success', \Yii::t('manager', 'msg_config_saved'));
            }

            if ($form->tablesAreCreated()) {
                $connConfig          = $form->getDBConfig();
                $connConfig['class'] = \yii\db\Connection::class;
                \yii::$app->set('db', $connConfig);

                if ($form->adminUsername != '' && $form->adminPassword != '') {
                    $form->createOrUpdateAdminAccount();
                }
                if ($form->adminUser) {
                    if ($form->getDefaultSite()) {
                        $form->updateSite();
                    } else {
                        $form->createSite();
                    }
                }
                if ($editable) {
                    $form->saveConfig();
                }
            }

            return $this->redirect($form->getConfig()->resourceBase);
        }

        $delInstallFileCmd = 'rm ' . $installFile;

        return $this->render('antragsgruen_init', [
            'form'                 => $form,
            'installFileDeletable' => is_writable($configDir),
            'delInstallFileCmd'    => $delInstallFileCmd,
            'editable'             => $editable,
            'makeEditabeCommand'   => $makeEditabeCommand,
        ]);
    }

    /**
     * @return string
     * @throws Internal
     */
    public function actionAntragsgrueninitdbtest()
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
        $form = new AntragsgruenInitForm($configFile);
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

    /**
     * @return string
     */
    public function actionPaymentadmin()
    {
        if (!User::currentUserIsSuperuser()) {
            return $this->showErrorpage(403, 'Only admins are allowed to access this page.');
        }

        /** @var Site[] $sites */
        $sites = Site::find()->where('status != ' . Site::STATUS_DELETED)->all();

        if ($this->isPostSet('save')) {
            $set    = \Yii::$app->request->post('billSent', []);
            $active = \Yii::$app->request->post('siteActive', []);
            foreach ($sites as $site) {
                $settings           = $site->getSettings();
                $settings->billSent = (in_array($site->id, $set) ? 1 : 0);
                $site->setSettings($settings);
                $site->status = (in_array($site->id, $active) ? Site::STATUS_ACTIVE : Site::STATUS_INACTIVE);
                $site->save();
            }
        }

        return $this->render('payment_admin', ['sites' => $sites]);
    }

    /**
     * @return string
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
