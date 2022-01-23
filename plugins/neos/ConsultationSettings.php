<?php

namespace app\plugins\neos;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public function getSpecializedLayoutClass(): string
    {
        return LayoutSettings::class;
    }
}
