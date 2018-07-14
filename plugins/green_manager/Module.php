<?php

namespace app\plugins\green_manager;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;
use app\models\events\UserEvent;
use app\models\layoutHooks\Hooks;
use app\models\settings\AntragsgruenApp;
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\ModuleBase;
use yii\base\Event;

class Module extends ModuleBase
{
    /**
     */
    public function init()
    {
        parent::init();

        Event::on(User::class, User::EVENT_ACCOUNT_CONFIRMED, [Module::class, 'onAccountConfirmed']);
    }

    /**
     * @param string $domainPlain
     * @return array
     */
    public static function getManagerUrlRoutes($domainPlain)
    {
        $domPlainPaths = 'help|password|createsite|check-subdomain|legal|privacy';
        return [
            $domainPlain                                    => 'green_manager/manager/index',
            $domainPlain . '/<_a:(' . $domPlainPaths . ')>' => 'green_manager/manager/<_a>',
        ];
    }

    /**
     * @return string
     */
    public static function getDefaultRouteOverride()
    {
        return '/green_manager/manager/index';
    }

    /**
     * @param \yii\web\Controller $controller
     * @return \yii\web\AssetBundle[]
     */
    public static function getActiveAssetBundles($controller)
    {
        if (strpos($controller->route, 'green_manager') === 0) {
            return [
                Assets::class,
            ];
        } else {
            return [];
        }
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
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
    public static function getSiteSettingsClass($site)
    {
        return SiteSettings::class;
    }

    /**
     * @return null|string
     */
    public static function overridesDefaultLayout()
    {
        return 'layout-plugin-green_layout-std';
    }

    /**
     * @return string;
     */
    public static function getCustomSiteCreateView()
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
