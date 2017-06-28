<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\Site;
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use Yii;
use yii\helpers\Url;

class UrlHelper
{
    /** @var null|Site */
    private static $currentSite = null;

    /** @var null|Consultation */
    private static $currentConsultation = null;

    /**
     * @param Site|null $site
     */
    public static function setCurrentSite($site)
    {
        static::$currentSite = $site;
    }

    /**
     * @param Consultation|null $consultation
     */
    public static function setCurrentConsultation($consultation)
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
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app;
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
                'consultation/legal',
                'consultation/privacy',
                'admin/index/admins',
                'admin/index/consultations',
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
     * @param string|array $route
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
            if (static::$currentConsultation->getSettings()->forceMotion) {
                $forceMotion = static::$currentConsultation->getSettings()->forceMotion;
                $motion      = static::$currentConsultation->getMotion($forceMotion);
                if ($motion) {
                    return static::createMotionUrl($motion);
                } else {
                    return static::createUrl('consultation/index');
                }
            } else {
                return static::createUrl('consultation/index');
            }
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

        if (static::$currentSite) {
            if ($params->domainSubdomain) {
                if (mb_strpos($url, $params->resourceBase) === 0) {
                    $url = mb_substr($url, mb_strlen($params->resourceBase));
                } elseif ($url[0] == '/') {
                    $url = mb_substr($url, 1);
                }
                $dom = str_replace('<subdomain:[\w_-]+>', static::$currentSite->subdomain, $params->domainSubdomain);
                return $dom . $url;
            } else {
                if ($url[0] == '/') {
                    $url = mb_substr($url, 1);
                }
                return $params->domainPlain . $url;
            }
        } else {
            if ($url[0] == '/') {
                $url = mb_substr($url, 1);
            }
            return $params->domainPlain . $url;
        }
    }

    /**
     * @param string $route
     * @return string
     */
    public static function createWurzelwerkLoginUrl($route)
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $target_url = Url::toRoute($route);

        if (Yii::$app->user->isGuest) {
            if ($params->isSamlActive()) {
                return Url::toRoute(['user/loginsaml', 'backUrl' => $target_url]);
            } elseif ($params->hasWurzelwerk) {
                return Url::toRoute(['user/loginwurzelwerk', 'backUrl' => $target_url]);
            } else {
                return '';
            }
        } else {
            return $target_url;
        }
    }

    /**
     * @param Motion $motion
     * @param string $mode
     * @param array $addParams
     * @return string
     */
    public static function createMotionUrl(Motion $motion, $mode = 'view', $addParams = [])
    {
        $params = array_merge(['motion/' . $mode, 'motionSlug' => $motion->getMotionSlug()], $addParams);
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
                'motionSlug' => $motionComment->motion->getMotionSlug(),
                'commentId'  => $motionComment->id,
                '#'          => 'comm' . $motionComment->id
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
            'motionSlug'  => $amendment->getMyMotion()->getMotionSlug(),
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
                'motionSlug'  => $amendmentComment->amendment->getMyMotion()->getMotionSlug(),
                'amendmentId' => $amendmentComment->amendmentId,
                'commentId'   => $amendmentComment->id,
                '#'           => 'comm' . $amendmentComment->id
            ]
        );
    }

    /**
     * Returns the subdomain or null, if this is the main domain
     * Throws an error if the given URL does not belong to the current system (hacking attempt?)
     *
     * @param string $url
     * @return string|null
     * @throws FormError
     */
    public static function getSubdomain($url)
    {
        /** @var AntragsgruenApp $params */
        $params = Yii::$app->params;

        $urlParts = parse_url($url);
        $scheme   = (isset($urlParts['scheme']) ? $urlParts['scheme'] : $_SERVER['REQUEST_SCHEME']);
        $host     = (isset($urlParts['host']) ? $urlParts['host'] : $_SERVER['HTTP_HOST']);
        $fullhost = $scheme . '://' . $host . '/';
        if ($params->domainPlain == $fullhost) {
            return null;
        } else {
            $preg = str_replace('<subdomain:[\\w_-]+>', '[\\w_-]+', $params->domainSubdomain);
            $preg = '/^' . preg_quote($preg, '/') . '$/u';
            $preg = str_replace('\\[\\\\w_\\-\\]\\+', '(?<subdomain>[\\w_-]+)', $preg);
            if (preg_match($preg, $fullhost, $matches)) {
                return $matches['subdomain'];
            } else {
                throw new FormError('Unknown domain: ' . $urlParts['host']);
            }
        }
    }
}
