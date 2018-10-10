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
     * @return Site|null
     */
    public static function getCurrentSite()
    {
        return static::$currentSite;
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
     * @param string $route
     * @return string[]
     */
    protected static function getRouteParts($route)
    {
        $parts = explode('/', $route);
        if (count($parts) === 3) {
            return [
                'module'     => ($parts[0] !== '' ? $parts[0] : null),
                'controller' => $parts[1],
                'action'     => $parts[2],
            ];
        } elseif (count($parts) === 2) {
            return [
                'module'     => null,
                'controller' => $parts[0],
                'action'     => $parts[1],
            ];
        } else {
            return [
                'module'     => null,
                'controller' => null,
                'view'       => $parts[0],
            ];
        }
    }

    /**
     * @param array $route
     * @return string
     */
    protected static function createSiteUrl($route)
    {
        $site         = static::$currentSite;
        $consultation = static::$currentConsultation;
        $routeParts   = static::getRouteParts($route[0]);

        if ($consultation !== null && !isset($route['consultationPath'])) {
            // for pages/show-page, consultationPath is optional
            if ($routeParts['controller'] !== 'pages' || !in_array($routeParts['action'], ['show-page', 'save-page'])) {
                $route['consultationPath'] = $consultation->urlPath;
            }
        }
        if (static::getParams()->multisiteMode && $site !== null) {
            $route['subdomain'] = $site->subdomain;
        }

        if ($routeParts['controller'] === 'user') {
            unset($route['consultationPath']);
        }
        if (in_array(
            trim($route[0], '/'),
            [
                'consultation/home',
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
     * @param null|Consultation $forceConsultation
     * @return string
     */
    public static function createUrl($route, $forceConsultation = null)
    {
        if (!is_array($route)) {
            $route = [$route];
        }

        if ($forceConsultation) {
            if (!static::$currentConsultation || $forceConsultation->id !== static::$currentConsultation->id) {
                $route['consultationPath'] = $forceConsultation->urlPath;
            }
            if (!static::$currentSite || $forceConsultation->site->id !== static::$currentSite->id) {
                $route['subdomain'] = $forceConsultation->site->subdomain;
            }
        }

        $routeParts = static::getRouteParts($route[0]);
        if ($routeParts['controller'] !== 'manager') {
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
            return static::createUrl(['/user/login', 'backUrl' => $target_url]);
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
            $consultation       = static::$currentConsultation;
            $homeOverride       = $consultation->site->getBehaviorClass()->hasSiteHomePage();
            $preferConsultation = $consultation->site->getBehaviorClass()->preferConsultationSpecificHomeLink();
            if ($preferConsultation) {
                $homeUrl = static::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
            } elseif ($consultation->site->currentConsultationId === $consultation->id || $homeOverride) {
                $homeUrl = static::createUrl('/consultation/home');
            } else {
                $homeUrl = static::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
            }

            if (static::$currentConsultation->getSettings()->forceMotion) {
                $forceMotion = static::$currentConsultation->getSettings()->forceMotion;
                $motion      = static::$currentConsultation->getMotion($forceMotion);
                if ($motion) {
                    return static::createMotionUrl($motion);
                } else {
                    return $homeUrl;
                }
            } else {
                return $homeUrl;
            }
        } else {
            foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
                if ($pluginClass::getDefaultRouteOverride()) {
                    return static::createUrl($pluginClass::getDefaultRouteOverride());
                }
            }
            return null;
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
                return Url::toRoute(['/user/loginsaml', 'backUrl' => $target_url]);
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
        $params = array_merge(['/motion/' . $mode, 'motionSlug' => $motion->getMotionSlug()], $addParams);
        return static::createUrl($params, $motion->getMyConsultation());
    }

    /**
     * @param MotionComment $motionComment
     * @return string
     */
    public static function createMotionCommentUrl(MotionComment $motionComment)
    {
        $params = [
            '/motion/view',
            'motionSlug' => $motionComment->motion->getMotionSlug(),
            'commentId'  => $motionComment->id,
            '#'          => 'comm' . $motionComment->id
        ];
        return static::createUrl($params, $motionComment->motion->getMyConsultation());
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
            '/amendment/' . $mode,
            'motionSlug'  => $amendment->getMyMotion()->getMotionSlug(),
            'amendmentId' => $amendment->id
        ], $addParams);
        return static::createUrl($params, $amendment->getMyConsultation());
    }

    /**
     * @param AmendmentComment $amendmentComment
     * @return string
     */
    public static function createAmendmentCommentUrl(AmendmentComment $amendmentComment)
    {
        $params = [
            '/amendment/view',
            'motionSlug'  => $amendmentComment->amendment->getMyMotion()->getMotionSlug(),
            'amendmentId' => $amendmentComment->amendmentId,
            'commentId'   => $amendmentComment->id,
            '#'           => 'comm' . $amendmentComment->id
        ];
        return static::createUrl($params, $amendmentComment->amendment->getMyConsultation());
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
