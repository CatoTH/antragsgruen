<?php

namespace app\components;

use app\models\db\{Amendment, AmendmentComment, Consultation, IMotion, Motion, MotionComment, Site};
use app\models\exceptions\{FormError, Internal};
use app\models\settings\AntragsgruenApp;
use Yii;
use yii\helpers\Url;

class UrlHelper
{
    private static ?Site $currentSite = null;
    private static ?Consultation $currentConsultation = null;

    public static function setCurrentSite(?Site $site): void
    {
        self::$currentSite = $site;
    }

    public static function getCurrentSite(): ?Site
    {
        return self::$currentSite;
    }

    public static function setCurrentConsultation(?Consultation $consultation): void
    {
        self::$currentConsultation = $consultation;
    }

    public static function getCurrentConsultation(): ?Consultation
    {
        return self::$currentConsultation;
    }

    private static function getParams(): AntragsgruenApp
    {
        /** @var AntragsgruenApp $app */
        $app = Yii::$app->params;
        return $app;
    }

    /**
     * @return array{module: string|null, controller: string|null, action?: string, view?: string}
     */
    protected static function getRouteParts(string $route): array
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

    protected static function createSiteUrl(array $route): string
    {
        $site         = self::$currentSite;
        $consultation = self::$currentConsultation;
        $routeParts   = self::getRouteParts($route[0]);

        if ($consultation !== null && !isset($route['consultationPath'])) {
            // for pages/show-page, consultationPath is optional
            if ($routeParts['controller'] !== 'pages' || !in_array(($routeParts['action'] ?? ''), ['show-page', 'save-page'])) {
                $route['consultationPath'] = $consultation->urlPath;
            }
        }
        if (self::getParams()->multisiteMode && $site !== null) {
            $route['subdomain'] = $site->subdomain;
        }

        if ($routeParts['controller'] === 'user' && !in_array($routeParts['action'] ?? '', ['consultationaccesserror', 'myaccount'])) {
            unset($route['consultationPath']);
        }
        if (in_array(
            trim($route[0], '/'),
            [
                'consultation/home',
                'consultation/rest-site',
                'admin/index/admins',
                'admin/index/consultations',
                'pages/css',
            ]
        )) {
            unset($route['consultationPath']);
        }
        $finalRoute = Url::toRoute($route);

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $overriddenRoute = $plugin::getGeneratedRoute($route, $finalRoute);
            if ($overriddenRoute) {
                $finalRoute = $overriddenRoute;
            }
        }

        return $finalRoute;
    }

    public static function createUrl(string|array $route, ?Consultation $forceConsultation = null): string
    {
        if (!is_array($route)) {
            $route = [$route];
        }

        if ($forceConsultation) {
            if (!self::$currentConsultation || $forceConsultation->id !== self::$currentConsultation->id) {
                $route['consultationPath'] = $forceConsultation->urlPath;
            }
            if (!self::$currentSite || $forceConsultation->site->id !== self::$currentSite->id) {
                $route['subdomain'] = $forceConsultation->site->subdomain;
            }
        }

        $routeParts = self::getRouteParts($route[0]);
        if ($routeParts['controller'] !== 'manager') {
            return self::createSiteUrl($route);
        } else {
            return Url::toRoute($route);
        }
    }

    public static function createLoginUrl(string|array $route): string
    {
        $target_url = self::createUrl($route);
        if (RequestContext::getWebApplication()->user->isGuest) {
            return self::createUrl(['/user/login', 'backUrl' => $target_url]);
        } else {
            return $target_url;
        }
    }

    public static function homeUrl(): ?string
    {
        if (self::$currentConsultation) {
            $consultation = self::$currentConsultation;
            $homeOverride = false;
            $preferConsultation = false;
            foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
                if ($plugin::hasSiteHomePage()) {
                    $homeOverride = true;
                }
                if ($plugin::preferConsultationSpecificHomeLink()) {
                    $preferConsultation = true;
                }
            }
            if ($preferConsultation) {
                $homeUrl = self::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
            } elseif ($consultation->site->currentConsultationId === $consultation->id || $homeOverride) {
                $homeUrl = self::createUrl('/consultation/home');
            } else {
                $homeUrl = self::createUrl(['/consultation/index', 'consultationPath' => $consultation->urlPath]);
            }

            if (self::$currentConsultation->getSettings()->forceMotion) {
                $forceMotion = self::$currentConsultation->getSettings()->forceMotion;
                $motion      = self::$currentConsultation->getMotion($forceMotion);
                if ($motion) {
                    return self::createMotionUrl($motion);
                } else {
                    return $homeUrl;
                }
            } else {
                return $homeUrl;
            }
        } else {
            foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
                if ($pluginClass::getDefaultRouteOverride()) {
                    return self::createUrl($pluginClass::getDefaultRouteOverride());
                }
            }
            return null;
        }
    }

    public static function absolutizeLink(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        $params = self::getParams();

        if (self::$currentSite) {
            if ($params->domainSubdomain) {
                if (grapheme_strpos($url, $params->resourceBase) === 0) {
                    $url = grapheme_substr($url, (int)grapheme_strlen($params->resourceBase));
                } elseif ($url[0] === '/') {
                    $url = grapheme_substr($url, 1);
                }
                $dom = str_replace('<subdomain:[\w_-]+>', self::$currentSite->subdomain, $params->domainSubdomain);
                return $dom . $url;
            } else {
                if ($url[0] === '/') {
                    $url = grapheme_substr($url, 1);
                }
                return $params->domainPlain . $url;
            }
        } else {
            if ($url[0] === '/') {
                $url = grapheme_substr($url, 1);
            }
            return $params->domainPlain . $url;
        }
    }

    public static function createIMotionUrl(IMotion $IMotion, string $mode = 'view', array $addParams = []): string
    {
        return match (get_class($IMotion)) {
            Motion::class => self::createMotionUrl($IMotion, $mode, $addParams),
            Amendment::class => self::createAmendmentUrl($IMotion, $mode, $addParams),
            default => throw new Internal('Unknown class: ' . get_class($IMotion))
        };
    }

    public static function createMotionUrl(Motion $motion, string $mode = 'view', array $addParams = []): string
    {
        $params = array_merge(['/motion/' . $mode, 'motionSlug' => $motion->getMotionSlug()], $addParams);
        return self::createUrl($params, $motion->getMyConsultation());
    }

    public static function createMotionCommentUrl(MotionComment $motionComment): string
    {
        $params = [
            '/motion/view',
            'motionSlug' => $motionComment->getIMotion()->getMotionSlug(),
            'commentId'  => $motionComment->id,
            '#'          => 'comm' . $motionComment->id
        ];
        return self::createUrl($params, $motionComment->getIMotion()->getMyConsultation());
    }

    public static function createAmendmentUrl(Amendment $amendment, string $mode = 'view', array $addParams = []): string
    {
        if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT && $amendment->getMyProposalReference()) {
            $amendment = $amendment->getMyProposalReference();
        }
        $params = array_merge([
            '/amendment/' . $mode,
            'motionSlug'  => $amendment->getMyMotion()->getMotionSlug(),
            'amendmentId' => $amendment->id
        ], $addParams);
        return self::createUrl($params, $amendment->getMyConsultation());
    }

    public static function createAmendmentCommentUrl(AmendmentComment $amendmentComment): string
    {
        $params = [
            '/amendment/view',
            'motionSlug'  => $amendmentComment->getIMotion()->getMyMotion()->getMotionSlug(),
            'amendmentId' => $amendmentComment->amendmentId,
            'commentId'   => $amendmentComment->id,
            '#'           => 'comm' . $amendmentComment->id
        ];
        return self::createUrl($params, $amendmentComment->getIMotion()->getMyConsultation());
    }

    public static function getCurrentScheme(): string
    {
        // Needs to be equal to Yii2's web/Request.php
        if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)) {
            return 'https';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0) {
            return 'https';
        }

        return 'http';
    }

    /*
     * Returns the subdomain or null, if this is the main domain
     * Throws an error if the given URL does not belong to the current system (hacking attempt?)
     */
    public static function getSubdomain(string $url): ?string
    {
        $params = AntragsgruenApp::getInstance();

        $urlParts = parse_url($url);
        if (!$urlParts) {
            throw new FormError('Unable to parse the url');
        }
        $scheme   = $urlParts['scheme'] ?? self::getCurrentScheme();
        $host     = $urlParts['host'] ?? $_SERVER['HTTP_HOST'];
        $fullhost = $scheme . '://' . $host . '/';
        if ($params->domainPlain === $fullhost) {
            return null;
        } else {
            $preg = str_replace('<subdomain:[\\w_-]+>', '[\\w_-]+', $params->domainSubdomain);
            $preg = '/^' . preg_quote($preg, '/') . '$/u';
            $preg = str_replace('\\[\\\\w_\\-\\]\\+', '(?<subdomain>[\\w_-]+)', $preg);
            if (preg_match($preg, $fullhost, $matches)) {
                return $matches['subdomain'];
            } else {
                throw new FormError('Unknown domain: ' . $host);
            }
        }
    }
}
