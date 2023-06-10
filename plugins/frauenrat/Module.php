<?php

namespace app\plugins\frauenrat;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\plugins\frauenrat\pdf\Frauenrat;
use app\plugins\ModuleBase;
use yii\helpers\Url;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'save-motion-proposal'    => 'frauenrat/motion/save-proposal',
            'save-motion-tag'         => 'frauenrat/motion/save-tag',
            'save-amendment-proposal' => 'frauenrat/amendment/save-proposal',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/Schwerpunktthemen.pdf'] = '/frauenrat/motion/schwerpunktthemen';
        $urls[$dom . '<consultationPath:[\w_-]+>/Sachantraege.pdf'] = '/frauenrat/motion/sachantraege';

        return $urls;
    }

    public static function getProvidedPdfLayouts(array $default): array
    {
        $default[] = [
            'id'        => 100,
            'title'     => 'Deutscher Frauenrat',
            'preview'   => null,
            'className' => Frauenrat::class,
        ];

        return $default;
    }

    public static function getCustomEmailTemplate(): ?string
    {
        return '@app/plugins/frauenrat/views/email';
    }

    public static function getGeneratedRoute(array $routeParts, string $originallyGeneratedRoute): ?string
    {
        if ($routeParts[0] === '/motion/pdf') {
            $routeParts[0] = '/motion/embedded-amendments-pdf';
            return Url::toRoute($routeParts);
        }
        return null;
    }
}
