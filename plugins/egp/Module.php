<?php

namespace app\plugins\egp;

use app\components\UrlHelper;
use app\models\db\{AmendmentSupporter, Consultation, Motion, MotionSupporter, Site};
use app\models\settings\IMotionStatus;
use app\plugins\egp\pdf\Egp;
use app\plugins\ModuleBase;
use yii\base\Event;
use yii\web\View;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();

        Event::on(AmendmentSupporter::class, AmendmentSupporter::EVENT_SUPPORTED, [Notifications::class, 'onAmendmentSupport'], null, false);
        Event::on(MotionSupporter::class, MotionSupporter::EVENT_SUPPORTED, [Notifications::class, 'onMotionSupport'], null, false);
    }

    /**
     * @param Site $site
     *
     * @return SiteSpecificBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return SiteSpecificBehavior::class;
    }

    public static function getProvidedLayouts(?View $view = null): array
    {
        if ($view) {
            $asset = ThumbnailAssets::register($view);
            $thumbBase = $asset->baseUrl;
        } else {
            $thumbBase = '/';
        }

        return [
            'std' => [
                'title'       => 'European Green Party',
                'preview'     => $thumbBase . '/layout-preview-green.png',
                'bundle'      => Assets::class,
                'hooks'       => LayoutHooks::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }

    public static function getCustomMotionExports(Motion $motion): array
    {
        return [
            'Spreadsheet' => UrlHelper::createUrl(['/egp/motion/ods', 'motionSlug' => $motion->getMotionSlug()]),
        ];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'ods'    => 'egp/motion/ods',
        ];
    }

    public static function getAllUrlRoutes(string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/candidatures'] = '/egp/candidatures/index';

        return $urls;
    }

    public static function getProvidedPdfLayouts(array $default): array
    {
        $default[] = [
            'id'        => 101,
            'title'     => 'European Greens',
            'preview'   => null,
            'className' => Egp::class,
        ];

        return $default;
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

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return \Yii::$app->controller->renderPartial(
            '@app/plugins/egp/views/admin/consultation_settings', ['consultation' => $consultation]
        );
    }

    /**
     * @return IMotionStatus[]
     */
    public static function getAdditionalIMotionStatuses(): array
    {
        return [
            new IMotionStatus(100, 'CAS accepted'),
            new IMotionStatus(101, 'CAS accepted as amended'),
            new IMotionStatus(102, 'CAS rejected'),
            new IMotionStatus(103, 'CAS rejected in favour of other'),
            new IMotionStatus(104, 'VOTE'),
            new IMotionStatus(105, 'VOTE on CAS AM as amended'),
            new IMotionStatus(106, 'Falls in favour of other'),
            new IMotionStatus(107, 'CAS withdrawn'),
        ];
    }
}
