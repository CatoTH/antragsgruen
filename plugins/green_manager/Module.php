<?php

namespace app\plugins\green_manager;

use app\models\db\{Consultation, Site, User};
use app\models\events\UserEvent;
use app\models\settings\Layout;
use app\plugins\ModuleBase;
use yii\base\Event;
use yii\web\Controller;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();

        Event::on(User::class, User::EVENT_ACCOUNT_CONFIRMED, [Module::class, 'onAccountConfirmed']);
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        $domPlainPaths = 'help|password|createsite|check-subdomain|legal|privacy|free-hosting';
        return [
            $domainPlain                                    => 'green_manager/manager/index',
            $domainPlain . '/<_a:(' . $domPlainPaths . ')>' => 'green_manager/manager/<_a>',
        ];
    }

    public static function getDefaultRouteOverride(): string
    {
        return '/green_manager/manager/index';
    }

    /**
     * @param Controller $controller
     * @return \yii\web\AssetBundle[]|string[]
     */
    public static function getActiveAssetBundles(Controller $controller)
    {
        if (strpos($controller->route, 'green_manager') === 0) {
            return [
                Assets::class,
            ];
        } else {
            return [];
        }
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation)
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    /**
     * @param Site $site
     * @return string|SiteSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSettingsClass(Site $site)
    {
        return SiteSettings::class;
    }

    public static function overridesDefaultLayout(): ?string
    {
        return 'layout-plugin-green_layout-std';
    }

    public static function getCustomSiteCreateView(): string
    {
        return "@app/plugins/green_manager/views/sitedata_subdomain";
    }

    /**
     * @param UserEvent $event
     */
    public static function onAccountConfirmed(UserEvent $event)
    {
        foreach ($event->user->adminSites as $site) {
            /** @var SiteSettings $settings */
            $settings              = $site->getSettings();
            $settings->isConfirmed = true;
            $site->setSettings($settings);
            $site->save();
        }
    }
}
