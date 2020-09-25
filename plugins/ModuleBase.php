<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\plugins;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\models\db\{Amendment, Consultation, Motion, Site};
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use yii\base\{Action, Module};
use yii\web\{AssetBundle, Controller, View};

class ModuleBase extends Module
{
    public function init(): void
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $ref                       = new \ReflectionClass($this);
            $this->controllerNamespace = $ref->getNamespaceName() . '\\commands';
        }
    }

    /**
     * @param Controller $controller
     *
     * @return AssetBundle[]
     */
    public static function getActiveAssetBundles(Controller $controller)
    {
        return [];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [];
    }

    protected static function getAmendmentUrlRoutes(): array
    {
        return [];
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [];
    }

    public static function getDefaultRouteOverride(): ?string
    {
        return null;
    }

    public static function getAllUrlRoutes(string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = [];
        foreach (static::getMotionUrlRoutes() as $url => $route) {
            $urls[$dommotion . '/' . $url]    = $route;
            $urls[$dommotionOld . '/' . $url] = $route;
        }
        foreach (static::getAmendmentUrlRoutes() as $url => $route) {
            $urls[$domamend . '/' . $url]    = $route;
            $urls[$domamendOld . '/' . $url] = $route;
        }

        return $urls;
    }

    /**
     * @param Site $site
     *
     * @return null|DefaultBehavior|string
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return null;
    }

    /**
     * @param Consultation $consultation
     *
     * @return string|\app\models\settings\Consultation|null
     */
    public static function getConsultationSettingsClass(Consultation $consultation)
    {
        return null;
    }

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return '';
    }

    /**
     * @param Site $site
     *
     * @return string|\app\models\settings\Site
     */
    public static function getSiteSettingsClass(Site $site)
    {
        return null;
    }

    public static function getProvidedLayouts(?View $view = null): array
    {
        return [];
    }

    public static function overridesDefaultLayout(): ?string
    {
        return null;
    }

    public static function getProvidedPdfLayouts(array $default): array
    {
        return $default;
    }

    public static function getCustomMotionExports(Motion $motion): array
    {
        return [];
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation|null $consultation
     *
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation)
    {
        return [];
    }

    public static function getCustomSiteCreateView(): ?string
    {
        return null;
    }

    public static function getCustomEmailTemplate(): ?string
    {
        return null;
    }

    public static function getRobotsIndexOverride(?Consultation $consultation, Action $action, bool $default): ?bool
    {
        return null;
    }

    public static function getDefaultLogo(): ?array
    {
        return null;
    }

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        return null;
    }

    public static function getMotionExtraSettingsForm(Motion $motion): string
    {
        return '';
    }

    public static function setMotionExtraSettingsFromForm(Motion $motion, array $post): void
    {
    }

    public static function getAmendmentExtraSettingsForm(Amendment $amendment): string
    {
        return '';
    }

    public static function setAmendmentExtraSettingsFromForm(Amendment $amendment, array $post): void
    {
    }
}
