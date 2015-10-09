<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\MotionComment;
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
     * @return Consultation|null
     */
    public static function getCurrentConsultation()
    {
        return static::$currentConsultation;
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
                'consultation/legal', 'admin/index/admins', 'admin/index/consultations'
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
            $route = [$route];
        }
        $route_parts = explode('/', $route[0]);
        if ($route_parts[0] != 'manager') {
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
        $target_url = static::createUrl($route);
        if (Yii::$app->user->isGuest) {
            return static::createUrl(['user/login', 'backUrl' => $target_url]);
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
     * @param string $url
     * @return string
     */
    public static function absolutizeLink($url)
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        $params = static::getParams();
        if (mb_strpos($url, $params->resourceBase) === 0) {
            $url = mb_substr($url, mb_strlen($params->resourceBase));
        } elseif ($url[0] == '/') {
            $url = mb_substr($url, 1);
        }

        if (static::$currentSite) {
            if ($params->domainSubdomain) {
                return str_replace(
                    '<subdomain:[\w_-]+>',
                    static::$currentSite->subdomain,
                    $params->domainSubdomain
                ) . $url;
            } else {
                return $params->domainPlain . $url;
            }
        } else {
            return $params->domainPlain . $url;
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
     * @param array $params
     * @return string
     */
    public static function createMotionUrl(Motion $motion, $mode = 'view', $addParams = [])
    {
        $params = array_merge(['motion/' . $mode, 'motionId' => $motion->id], $addParams);
        return static::createUrl($params);
    }

    /**
     * @param MotionComment $motionComment
     * @return string
     */
    public static function createMotionCommentUrl(MotionComment $motionComment)
    {
        return static::createUrl(
            [
                'motion/view',
                'motionId'  => $motionComment->motionId,
                'commentId' => $motionComment->id,
                '#'         => 'comm' . $motionComment->id
            ]
        );
    }

    /**
     * @param Amendment $amendment
     * @param string $mode
     * @param array $addParams
     * @return string
     */
    public static function createAmendmentUrl(Amendment $amendment, $mode = 'view', $addParams = [])
    {
        $params = array_merge([
            'amendment/' . $mode,
            'motionId'    => $amendment->motionId,
            'amendmentId' => $amendment->id
        ], $addParams);
        return static::createUrl($params);
    }

    /**
     * @param AmendmentComment $amendmentComment
     * @return string
     */
    public static function createAmendmentCommentUrl(AmendmentComment $amendmentComment)
    {
        return static::createUrl(
            [
                'amendment/view',
                'motionId'    => $amendmentComment->amendment->motionId,
                'amendmentId' => $amendmentComment->amendmentId,
                'commentId'   => $amendmentComment->id,
                '#'           => 'comm' . $amendmentComment->id
            ]
        );
    }
}
