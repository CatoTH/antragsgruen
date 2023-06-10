<?php

namespace app\plugins\green_manager;

use app\models\db\{Consultation, ConsultationUserGroup, Site, User};
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
     * @return \yii\web\AssetBundle[]|string[]
     */
    public static function getActiveAssetBundles(Controller $controller): array
    {
        if (str_starts_with($controller->route, 'green_manager')) {
            return [
                Assets::class,
            ];
        } else {
            return [];
        }
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSettingsClass(Site $site): string
    {
        return SiteSettings::class;
    }

    public static function overridesDefaultLayout(): ?string
    {
        return 'layout-plugin-green_layout-std';
    }

    public static function onAccountConfirmed(UserEvent $event): void
    {
        foreach ($event->user->userGroups as $userGroup) {
            if ($userGroup->siteId && $userGroup->site &&
                $userGroup->templateId === ConsultationUserGroup::TEMPLATE_SITE_ADMIN) {
                $site = $userGroup->site;
                /** @var SiteSettings $settings */
                $settings = $site->getSettings();
                $settings->isConfirmed = true;
                $site->setSettings($settings);
                $site->save();
            }
        }
    }
}
