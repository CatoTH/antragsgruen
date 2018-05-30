<?php

namespace app\plugins\neos;

use app\models\db\Consultation;
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return array
     */
    public static function getProvidedLayouts()
    {
        return [
            'std' => [
                'title'  => 'NEOS',
                'bundle' => Assets::class,
            ]
        ];
    }

    /**
     * @param Consultation $consultation
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass($consultation)
    {
        return ConsultationSettings::class;
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
}
