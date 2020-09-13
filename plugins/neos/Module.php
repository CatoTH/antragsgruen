<?php

namespace app\plugins\neos;

use app\models\db\{Consultation, Site};
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();
    }

    public static function getProvidedLayouts(?View $view = null): array
    {
        if ($view) {
            $asset     = ThumbnailAssets::register($view);
            $thumbBase = $asset->baseUrl;
        } else {
            $thumbBase = '/';
        }

        return [
            'std' => [
                'title'   => 'NEOS',
                'preview' => $thumbBase . '/layout-preview-neos.png',
                'bundle'  => Assets::class,
            ]
        ];
    }

    public static function overridesDefaultLayout(): ?string
    {
        return 'layout-plugin-neos-std';
    }

    /**
     * @param Consultation $consultation
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass(Consultation $consultation)
    {
        return ConsultationSettings::class;
    }

    /**
     * @param Site $site
     * @return null|DefaultBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return SiteSpecificBehavior::class;
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation)
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getDefaultLogo(): ?array
    {
        return [
            'image/png',
            \Yii::$app->basePath . '/plugins/neos/assets/neos-antragsschmiede.png'
        ];
    }
}
