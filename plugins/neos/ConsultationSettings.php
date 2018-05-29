<?php

namespace app\plugins\neos;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    /**
     * @return null|string|LayoutSettings
     */
    public function getSpecializedLayoutClass()
    {
        return LayoutSettings::class;
    }
}
