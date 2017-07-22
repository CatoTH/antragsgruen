<?php

namespace app\controllers;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Layout;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;
use Yii;
use yii\base\Module;
use yii\helpers\Html;
use yii\web\Controller;

class Base extends Controller
{
    /** @var Layout */
    public $layoutParams = null;

    /** @var null|Consultation */
    public $consultation = null;

    /** @var null|Site */
    public $site = null;

    /** @var string */
    public $layout = 'column1';

    /**
     * @param string $cid the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($cid, $module, $config = [])
    {
        parent::__construct($cid, $module, $config);
        $this->layoutParams = new Layout();
    }

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        \yii::$app->response->headers->add('X-Xss-Protection', '1');
        \yii::$app->response->headers->add('X-Content-Type-Options', 'nosniff');
        \yii::$app->response->headers->add('X-Frame-Options', 'sameorigin');

        if (!parent::beforeAction($action)) {
            return false;
        }

        $params = \Yii::$app->request->resolve();
        /** @var AntragsgruenApp $appParams */
        $appParams = \Yii::$app->params;

        $inManager = (get_class($this) == ManagerController::class);
        $inInstaller = (get_class($this) == InstallationController::class);

        if ($appParams->siteSubdomain) {
            if (strpos($appParams->siteSubdomain, 'xn--') === 0) {
                HTMLTools::loadNetIdna2();
                $idna      = new \Net_IDNA2();
                $subdomain = $idna->decode($appParams->siteSubdomain);
            } else {
                $subdomain = $appParams->siteSubdomain;
            }

            $consultation = (isset($params[1]['consultationPath']) ? $params[1]['consultationPath'] : '');
            $this->loadConsultation($subdomain, $consultation);
            if ($this->site) {
                $this->layoutParams->setLayout($this->site->getSettings()->siteLayout);
            } else {
                $this->layoutParams->setLayout(Layout::DEFAULT_LAYOUT);
            }
        } elseif (isset($params[1]['subdomain'])) {
            if (strpos($params[1]['subdomain'], 'xn--') === 0) {
                HTMLTools::loadNetIdna2();
                $idna      = new \Net_IDNA2();
                $subdomain = $idna->decode($params[1]['subdomain']);
            } else {
                $subdomain = $params[1]['subdomain'];
            }

            $consultation = (isset($params[1]['consultationPath']) ? $params[1]['consultationPath'] : '');
            $this->loadConsultation($subdomain, $consultation);
            if ($this->site) {
                $this->layoutParams->setLayout($this->site->getSettings()->siteLayout);
            } else {
                $this->layoutParams->setLayout(Layout::DEFAULT_LAYOUT);
            }
        } elseif (!($inInstaller || $inManager) && !$appParams->multisiteMode) {
            $this->layoutParams->setLayout(Layout::DEFAULT_LAYOUT);
            $this->showErrorpage(500, \Yii::t('base', 'err_no_site_internal'));
        } else {
            $this->layoutParams->setLayout(Layout::DEFAULT_LAYOUT);
        }

        // Login and Mainainance mode is always allowed
        if (get_class($this) == UserController::class) {
            return true;
        }
        $allowedActions = ['maintenance', 'help', 'legal', 'privacy'];
        if (get_class($this) == ConsultationController::class && in_array($action->id, $allowedActions)) {
            return true;
        }

        if ($this->testMaintenanceMode() || $this->testSiteForcedLogin()) {
            return false;
        }
        return true;
    }

    /**
     * @param array|string $url
     * @param int $statusCode
     * @return mixed
     * @throws \yii\base\ExitException
     */
    public function redirect($url, $statusCode = 302)
    {
        $response = parent::redirect($url, $statusCode);
        \Yii::$app->end();
        return $response;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isPostSet($name)
    {
        $post = \Yii::$app->request->post();
        return isset($post[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isGetSet($name)
    {
        $get = \Yii::$app->request->get();
        return isset($get[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRequestSet($name)
    {
        return $this->isPostSet($name) || $this->isGetSet($name);
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed
     */
    public function getRequestValue($name, $default = null)
    {
        $post = \Yii::$app->request->post();
        if (isset($post[$name])) {
            return $post[$name];
        }
        $get = \Yii::$app->request->get();
        if (isset($get[$name])) {
            return $get[$name];
        }
        return $default;
    }

    /**
     * @param string $pageKey
     * @return string
     */
    protected function renderContentPage($pageKey)
    {
        if ($this->consultation) {
            $admin   = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT);
            $saveUrl = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => $pageKey]);
        } else {
            $user    = User::getCurrentUser();
            $admin   = ($user && in_array($user->id, $this->getParams()->adminUserIds));
            $saveUrl = UrlHelper::createUrl(['manager/savetextajax', 'pageKey' => $pageKey]);
        }
        return $this->render(
            '@app/views/consultation/contentpage',
            [
                'pageKey' => $pageKey,
                'admin'   => $admin,
                'saveUrl' => $saveUrl
            ]
        );
    }

    /**
     * @param string $view
     * @param array $options
     * @return string
     */
    public function render($view, $options = array())
    {
        $params = array_merge(
            [
                'consultation' => $this->consultation,
            ],
            $options
        );
        return parent::render($view, $params);
    }

    /**
     * @return \app\models\settings\AntragsgruenApp
     */
    public function getParams()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app;
    }

    /**
     * @param int $privilege
     * @return bool
     * @throws Internal
     */
    public function currentUserHasPrivilege($privilege)
    {
        if (!$this->consultation) {
            throw new Internal('No consultation set');
        }
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this->consultation, $privilege);
    }


    /**
     * @return bool
     */
    public function testMaintenanceMode()
    {
        if ($this->consultation == null) {
            return false;
        }
        /** @var \app\models\settings\Consultation $settings */
        $settings = $this->consultation->getSettings();
        $admin    = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONSULTATION_SETTINGS);
        if ($settings->maintenanceMode && !$admin) {
            $this->redirect(UrlHelper::createUrl('consultation/maintenance'));
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function testSiteForcedLogin()
    {
        if ($this->site == null) {
            return false;
        }
        if (!$this->site->getSettings()->forceLogin) {
            return false;
        }
        if (\Yii::$app->user->getIsGuest()) {
            $this->redirect(UrlHelper::createUrl(['user/login', 'backUrl' => $_SERVER['REQUEST_URI']]));
            return true;
        }
        if ($this->site->getSettings()->managedUserAccounts) {
            if ($this->consultation && !User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_ANY)) {
                $privilege = User::getCurrentUser()->getConsultationPrivilege($this->consultation);
                if (!$privilege || !$privilege->privilegeView) {
                    $this->redirect(UrlHelper::createUrl('user/consultationaccesserror'));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     */
    public function forceLogin()
    {
        if (\Yii::$app->user->getIsGuest()) {
            $loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => \yii::$app->request->url]);
            $this->redirect($loginUrl);
            \yii::$app->end();
        }
    }

    /**
     * @return string
     */
    public function showErrors()
    {
        $str = '';

        $error = \Yii::$app->session->getFlash('error', null, true);
        if ($error) {
            $str = '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . nl2br(Html::encode($error)) . '
            </div>';
        }

        $success = \Yii::$app->session->getFlash('success', null, true);
        if ($success) {
            $str .= '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($success) . '
            </div>';
        }

        $info = \Yii::$app->session->getFlash('info', null, true);
        if ($info) {
            $str .= '<div class="alert alert-info" role="alert">
                <span class="glyphicon glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                <span class="sr-only">Info:</span>
                ' . Html::encode($info) . '
            </div>';
        }

        $email = \Yii::$app->session->getFlash('email', null, true);
        if ($email && YII_ENV == 'test') {
            $str .= '<div class="alert alert-info" role="alert">
                <span class="glyphicon glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                <span class="sr-only">Info:</span>
                ' . Html::encode($email) . '
            </div>';
        }

        return $str;
    }

    /**
     * @param $status
     * @param $message
     * @throws \yii\base\ExitException
     */
    protected function showErrorpage($status, $message)
    {
        if ($this->layoutParams->hooks === null) {
            $this->layoutParams->setLayout(Layout::DEFAULT_LAYOUT);
        }
        $this->layoutParams->robotsNoindex = true;
        Yii::$app->response->statusCode    = $status;
        Yii::$app->response->content       = $this->render(
            '@app/views/errors/error',
            [
                "message" => $message
            ]
        );
        Yii::$app->end();
    }

    /**
     * @throws \yii\base\ExitException
     */
    protected function consultationNotFound()
    {
        $url     = Html::encode($this->getParams()->domainPlain);
        $message = str_replace('%URL%', $url, \Yii::t('base', 'err_cons_404'));
        $this->showErrorpage(404, $message);
    }

    /**
     * @throws \yii\base\ExitException
     */
    protected function consultationError()
    {
        $this->showErrorpage(500, \Yii::t('base', 'err_site_404'));
    }

    /**
     * @param null|Motion $checkMotion
     * @param null|Amendment $checkAmendment
     */
    protected function checkConsistency($checkMotion = null, $checkAmendment = null)
    {
        $consultationId = strtolower($this->consultation->urlPath);
        $subdomain      = strtolower($this->site->subdomain);

        if (strtolower($this->consultation->site->subdomain) != $subdomain) {
            Yii::$app->user->setFlash("error", \Yii::t('base', 'err_cons_not_site'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if (is_object($checkMotion) && strtolower($checkMotion->getMyConsultation()->urlPath) != $consultationId) {
            Yii::$app->session->setFlash('error', \Yii::t('motion', 'err_not_found'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        if ($checkAmendment != null && ($checkMotion == null || $checkAmendment->motionId != $checkMotion->id)) {
            Yii::$app->session->setFlash('error', \Yii::t('base', 'err_amend_not_consult'));
            $this->redirect(UrlHelper::createUrl('consultation/index'));
        }
    }

    /**
     * @param string $subdomain
     * @param string $consultationId
     * @param null|Motion $checkMotion
     * @param null|Amendment $checkAmendment
     * @return null|Consultation
     */
    public function loadConsultation($subdomain, $consultationId = '', $checkMotion = null, $checkAmendment = null)
    {
        if (is_null($this->site)) {
            $this->site = Site::findOne(['subdomain' => $subdomain]);
        }
        if (is_null($this->site) || $this->site->status == Site::STATUS_DELETED || $this->site->dateDeletion !== null) {
            $this->consultationNotFound();
        }

        if ($consultationId == '') {
            $consultationId = $this->site->currentConsultation->urlPath;
        }

        if (is_null($this->consultation)) {
            $this->consultation = Consultation::findOne(['urlPath' => $consultationId, 'siteId' => $this->site->id]);
        }
        if (is_null($this->consultation) || $this->consultation->dateDeletion !== null) {
            $this->consultationNotFound();
        } else {
            Consultation::setCurrent($this->consultation);
        }

        UrlHelper::setCurrentConsultation($this->consultation);
        UrlHelper::setCurrentSite($this->site);

        $this->layoutParams->setConsultation($this->consultation);

        $this->checkConsistency($checkMotion, $checkAmendment);

        return $this->consultation;
    }
}
