<?php

namespace app\plugins\neos;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    /**
     * @return class-string<LayoutSettings>
     */
    public function getSpecializedLayoutClass(): string
    {
        return LayoutSettings::class;
    }
}
