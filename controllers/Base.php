<?php

namespace app\controllers;

use app\components\UrlHelper;
use app\models\exceptions\Internal;
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
        if (parent::beforeAction($action)) {
            $params = \Yii::$app->request->resolve();
            if (isset($params[1]['subdomain'])) {
                $consultation = (isset($params[1]['consultationPath']) ? $params[1]['consultationPath'] : '');
                $this->loadConsultation($params[1]['subdomain'], $consultation);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $pageKey
     * @return string
     */
    protected function renderContentPage($pageKey)
    {
        $admin   = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT);
        $saveUrl = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => $pageKey]);
        return $this->render(
            'contentpage',
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
                'test'         => 1,
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
        return \Yii::$app->params;
    }

    /**
     * @param int $privilege
     * @return bool
     * @throws Internal
     */
    public function currentUserHasPrivilege($privilege)
    {
        if (!$this->consultation) {
            throw new Internal("No consultation set");
        }
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this->consultation, $privilege);
    }


    /**
     *
     */
    public function testMaintainanceMode()
    {
        if ($this->consultation == null) {
            return;
        }
        /** @var \app\models\settings\Consultation $settings */
        $settings = $this->consultation->getSettings();
        $admin = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONSULTATION_SETTINGS);
        if ($settings->maintainanceMode && !$admin) {
            $this->redirect(UrlHelper::createUrl("consultation/maintainance"));
        }

        if ($this->site->getBehaviorClass()->isLoginForced() && Yii::$app->user->isGuest) {
            $this->redirect(UrlHelper::createUrl("user/login"));
        }
    }

    /**
     * @return string
     */
    public function showErrors()
    {
        $error = \Yii::$app->session->getFlash('error', null, true);
        if ($error) {
            $str = '<div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . nl2br(Html::encode($error)) . '
            </div>';
        } else {
            $str = "";
        }

        $success = \Yii::$app->session->getFlash('success', null, true);
        if ($success) {
            $str .= '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($success) . '
            </div>';
        } else {
            $str .= "";
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
        $this->layoutParams->robotsNoindex = true;
        echo $this->render(
            '@app/views/errors/error',
            [
                "message" => $message
            ]
        );
        Yii::$app->end($status);
    }

    /**
     * @throws \yii\base\ExitException
     */
    protected function consultationNotFound()
    {
        $message = "Die angegebene Veranstaltung wurde nicht gefunden. " .
            "Höchstwahrscheinlich liegt da an einem Tippfehler in der Adresse im Browser.<br>
					<br>
					Auf der <a href='https://www.antragsgruen.de/'>Antragsgrün-Startseite</a> " .
            "siehst du rechts eine Liste der aktiven Veranstaltungen.";
        $this->showErrorpage(404, $message);
    }

    /**
     * @throws \yii\base\ExitException
     */
    protected function consultationError()
    {
        $message = "Leider existiert die aufgerufene Seite nicht. " .
            "Falls du der Meinung bist, dass das ein Fehler ist, " .
            "melde dich bitte per E-Mail (info@antragsgruen.de) bei uns.";

        $this->showErrorpage(500, $message);
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
            Yii::$app->user->setFlash(
                "error",
                "Fehlerhafte Parameter - " .
                "die Veranstaltung gehört nicht zur Veranstaltungsreihe."
            );
            $this->redirect(UrlHelper::createUrl(['consultation/index', "consultation_id" => $consultationId]));
            Yii::$app->end();
        }

        if (is_object($checkMotion) && strtolower($checkMotion->consultation->urlPath) != $consultationId) {
            Yii::$app->session->setFlash('error', 'Der Antrag gehört nicht zur Veranstaltung.');
            $this->redirect(UrlHelper::createUrl(['consultation/index', "consultation_id" => $consultationId]));
            Yii::$app->end();
        }

        if ($checkAmendment != null && ($checkMotion == null || $checkAmendment->motionId != $checkMotion->id)) {
            Yii::$app->session->setFlash('error', 'Der Änderungsantrag gehört nicht zum Antrag.');
            $this->redirect(UrlHelper::createUrl(['consultation/index', "consultation_id" => $consultationId]));
            Yii::$app->end();
        }
    }

    /**
     * @param string $subdomain
     * @param string $consultationId
     * @param null|Motion $checkMotion
     * @param null|Amendment $checkAmendment
     * @return null|Consultation
     */
    public function loadConsultation($subdomain, $consultationId = "", $checkMotion = null, $checkAmendment = null)
    {
        if (is_null($this->site)) {
            $this->site = Site::findOne(["subdomain" => $subdomain]);
        }
        if (is_null($this->site)) {
            $this->consultationNotFound();
        }

        if ($consultationId == "") {
            $consultationId = $this->site->currentConsultation->urlPath;
        }

        if (is_null($this->consultation)) {
            $this->consultation = Consultation::findOne(["urlPath" => $consultationId, "siteId" => $this->site->id]);
        }

        UrlHelper::setCurrentConsultation($this->consultation);
        UrlHelper::setCurrentSite($this->site);

        $this->layoutParams->setConsultation($this->consultation);

        $this->checkConsistency($checkMotion, $checkAmendment);

        return $this->consultation;
    }
}
