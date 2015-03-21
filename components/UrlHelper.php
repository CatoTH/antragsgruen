<?php

namespace app\components;


use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\Site;
use Yii;
use yii\helpers\Url;

class UrlHelper
{
    /** @var null|Site */
    private static $currentSite = null;

    /** @var null|Consultation */
    private static $currentConsultation = null;

    /**
     * @param Site $site
     */
    public static function setCurrentSite(Site $site)
    {
        static::$currentSite = $site;
    }

    /**
     * @param Consultation $consultation
     */
    public static function setCurrentConsultation(Consultation $consultation)
    {
        static::$currentConsultation = $consultation;
    }


    /**
     * @return \app\models\settings\AntragsgruenApp
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
        $site         = static::$currentSite;
        $consultation = static::$currentConsultation;
        if ($consultation !== null && !isset($route['consultationPath'])) {
            $route['consultationPath'] = $consultation->urlPath;
        }
        if (static::getParams()->multisiteMode && $site != null) {
            $route['subdomain'] = $site->subdomain;
        }

        if ($route[0] == 'consultation/index' && !is_null($site) &&
            strtolower($route['consultationPath']) === strtolower($site->currentConsultation->urlPath)
        ) {
            unset($route['consultationPath']);
        }
        $parts = explode('/', $route[0]);
        if ($parts[0] == 'user') {
            unset($route['consultationPath']);
        }
        if (in_array(
            $route[0],
            [
                'veranstaltung/impressum', '/admin/index/reiheAdmins', '/admin/index/reiheVeranstaltungen'
            ]
        )) {
            unset($route['consultationPath']);
        }
        return Url::toRoute($route);
    }

    /**
     * @param string|array $route
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
     * @return string
     */
    public static function homeUrl()
    {
        if (static::$currentConsultation) {
            return static::createUrl('consultation/index');
        } else {
            return static::createUrl('manager/index');
        }
    }

    /**
     * @param string $route
     * @return string
     */
    public static function createWurzelwerkLoginUrl($route)
    {
        $target_url = Url::toRoute($route);
        if (Yii::$app->user->isGuest) {
            return Url::toRoute(['user/loginwurzelwerk', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }

    /**
     * @param Motion $motion
     * @param string $mode
     * @return string
     */
    public static function createMotionUrl(Motion $motion, $mode = 'view')
    {
        return static::createUrl(['motion/' . $mode, 'motionId' => $motion->id]);
    }
}
