<?php

namespace app\components;


use app\models\db\Consultation;
use app\models\db\Site;
use Yii;
use yii\helpers\Url;

class UrlHelper
{
    /** @var null|Site */
    private static $currentSite = null;

    /** @var null|Consultation */
    private static $currentConsultation = null;

    public static function setCurrentSite(Site $site)
    {
        static::$currentSite = $site;
    }

    public static function setCurrentConsultation(Consultation $consultation)
    {
        static::$currentConsultation = $consultation;
    }


    /**
     * @return \app\models\AntragsgruenAppParams
     */
    private static function getParams()
    {
        return \Yii::$app->params;
    }


    /**
     * @param array $route
     * @return string
     */
    protected static function createSiteUrl($route)
    {
        $site = static::$currentSite;
        $consultation = static::$currentConsultation;
        if ($consultation !== null) {
            $route["consultationPath"] = $consultation->urlPath;
        }
        if (static::getParams()->multisiteMode && $site != null) {
            $route["subdomain"] = $site->subdomain;
        }
        if ($route[0] == "consultation/index" && !is_null($site) &&
            strtolower($route["consultationPath"]) === strtolower($site->currentConsultation->urlPath)
        ) {
            unset($route["consultationPath"]);
        }
        if (in_array(
            $route[0],
            [
                "veranstaltung/ajaxEmailIstRegistriert", "veranstaltung/anmeldungBestaetigen",
                "veranstaltung/benachrichtigungen", "veranstaltung/impressum", "user/login",
                "user/logout", "/admin/index/reiheAdmins", "/admin/index/reiheVeranstaltungen"
            ]
        )) {
            unset($route["consultationPath"]);
        }
        return Url::toRoute($route);
    }

    /**
     * @param string|string $route
     * @return string
     */
    public static function createUrl($route)
    {
        if (!is_array($route)) {
            $route = array($route);
        }
        $route_parts = explode('/', $route[0]);
        if ($route_parts[0] != "manager") {
            return static::createSiteUrl($route);
        } else {
            return Url::toRoute($route);
        }
    }

    /**
     * @param string $route
     * @return string
     */
    public static function createLoginUrl($route)
    {
        $target_url = Url::toRoute($route);
        if (Yii::$app->user->isGuest) {
            return Url::toRoute(['user/login', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }

    /**
     * @param string $route
     * @return string
     */
    public function createWurzelwerkLoginUrl($route)
    {
        $target_url = Url::toRoute($route);
        if (Yii::$app->user->isGuest) {
            return Url::toRoute(['user/loginwurzelwerk', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }
}
