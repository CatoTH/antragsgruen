<?php

namespace app\controllers;


use app\models\ConsultationSettings;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\LayoutParams;
use Yii;
use yii\base\Module;
use yii\helpers\Url;
use yii\web\Controller;

class Base extends Controller
{
    /** @var LayoutParams */
    public $layoutParams = null;

    /** @var null|Consultation */
    public $consultation = null;

    /** @var null|Site */
    public $site = null;

    /**
     * @param string $cid the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($cid, $module, $config = [])
    {
        parent::__construct($cid, $module, $config);
        $this->layoutParams = new LayoutParams();
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
     * @return \app\models\AntragsgruenAppParams
     */
    public function getParams()
    {
        return \Yii::$app->params;
    }

    /**
     *
     */
    public function testeWartungsmodus()
    {
        if ($this->consultation == null) {
            return;
        }
        /** @var ConsultationSettings $settings */
        $settings = $this->consultation->getSettings();
        if ($settings->wartungs_modus_aktiv && !$this->consultation->isAdminCurUser()) {
            $this->redirect($this->createUrl("consultation/maintainance"));
        }

        if (veranstaltungsspezifisch_erzwinge_login($this->veranstaltung) && Yii::$app->user->isGuest) {
            $this->redirect($this->createUrl("veranstaltung/login"));
        }
    }

    /**
     * @param string $route
     * @return string
     */
    protected function createSiteUrl($route)
    {
        if ($this->consultation !== null) {
            $params["consultationId"] = $this->consultation->urlPath;
        }
        if ($this->getParams()->multisiteMode && $this->site != null) {
            $params["siteId"] = $this->site->subdomain;
        }
        if ($route == "consultation/index" && !is_null($this->site) &&
            strtolower($route["consultationId"]) === strtolower($this->site->currentConsultation->urlPath)
        ) {
            unset($route["consultationId"]);
        }
        if (in_array(
            $route,
            [
                "veranstaltung/ajaxEmailIstRegistriert", "veranstaltung/anmeldungBestaetigen",
                "veranstaltung/benachrichtigungen", "veranstaltung/impressum", "veranstaltung/login",
                "veranstaltung/logout", "/admin/index/reiheAdmins", "/admin/index/reiheVeranstaltungen"
            ]
        )) {
            unset($route["consultationId"]);
        }
        return Url::toRoute($route);
    }

    /**
     * @param string $route
     * @return string
     */
    public function createUrl($route)
    {
        $route_parts = explode('/', $route[0]);
        if ($route_parts[0] != "manager") {
            return $this->createSiteUrl($route);
        } else {
            return Url::toRoute($route);
        }
    }

    /**
     * @param string $route
     * @return string
     */
    public function createLoginUrl($route)
    {
        $target_url = Url::toRoute($route);
        if (Yii::$app->user->isGuest) {
            return Url::toRoute(['user/login', 'login_goto' => $target_url]);
        } else {
            return $target_url;
        }
    }

    /**
     * @throws \yii\base\ExitException
     */
    protected function consultationNotFound()
    {
        $this->layoutParams->robots_noindex = true;
        $this->render(
            'error',
            [
                "code"    => 404,
                "html"    => true,
                "message" => "Die angegebene Veranstaltung wurde nicht gefunden. " .
                    "Höchstwahrscheinlich liegt da an einem Tippfehler in der Adresse im Browser.<br>
					<br>
					Auf der <a href='http://www.antragsgruen.de/'>Antragsgrün-Startseite</a> " .
                    "siehst du rechts eine Liste der aktiven Veranstaltungen."
            ]
        );
        Yii::$app->end();
    }

    /**
     *
     */
    protected function consultationError()
    {
        $this->layoutParams->robots_noindex = true;
        $this->render(
            "../veranstaltung/error",
            [
                "code"    => 500,
                "message" => "Leider existiert die aufgerufene Seite nicht. " .
                    "Falls du der Meinung bist, dass das ein Fehler ist, " .
                    "melde dich bitte per E-Mail (info@antragsgruen.de) bei uns.",
            ]
        );
        Yii::$app->end(500);
    }

    /**
     * @param null|Motion $checkMotion
     * @param null|Amendment $checkAmendment
     */
    protected function checkConsistency($checkMotion = null, $checkAmendment = null)
    {
        $consultationId = strtolower($this->consultation->urlPath);
        $siteId         = strtolower($this->site->subdomain);

        if (strtolower($this->consultation->site->subdomain) != $siteId) {
            Yii::$app->user->setFlash(
                "error",
                "Fehlerhafte Parameter - " .
                "die Veranstaltung gehört nicht zur Veranstaltungsreihe."
            );
            $this->redirect($this->createUrl(['consultation/index', "consultation_id" => $consultationId]));
        }

        if (is_object($checkMotion) && strtolower($checkMotion->consultation->urlPath) != $consultationId) {
            Yii::$app->user->setFlash("error", "Fehlerhafte Parameter - der Antrag gehört nicht zur Veranstaltung.");
            $this->redirect($this->createUrl(['consultation/index', "consultation_id" => $consultationId]));
        }

        if ($checkAmendment != null && ($checkMotion == null || $checkAmendment->motionId != $checkAmendment->id)) {
            Yii::$app->user->setFlash("error", "Fehlerhafte Parameter - der Änderungsantrag gehört nicht zum Antrag.");
            $this->redirect($this->createUrl(['consultation/index', "consultation_id" => $consultationId]));
        }
    }

    /**
     * @param string $siteId
     * @param string $consultationId
     * @param null|Motion $checkMotion
     * @param null|Amendment $checkAmendment
     * @return null|Consultation
     */
    public function loadConsultation($siteId, $consultationId = "", $checkMotion = null, $checkAmendment = null)
    {
        if (is_null($this->site)) {
            $this->site = Site::findOne(["subdomain" => $siteId]);
        }

        if ($consultationId == "") {
            $consultationId = $this->site->currentConsultation->urlPath;
        }

        if (is_null($this->consultation)) {
            $this->consultation = Consultation::findOne(["url_path" => $consultationId]);
        }

        $this->checkConsistency($checkMotion, $checkAmendment);

        return $this->consultation;
    }
}
