<?php

namespace app\plugins\frauenrat;

use app\models\db\Consultation;
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\plugins\frauenrat\pdf\Frauenrat;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     *
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
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
}
