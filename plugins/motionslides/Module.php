<?php

declare(strict_types=1);

namespace app\plugins\motionslides;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/page/<pageSlug:[^\/]+>/from-imotions'] = '/motionslides/page/from-imotions';

        return $urls;
    }
}
