<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public string $defaultVersionFilter = '';

    public function getStartLayoutView(): ?string
    {
        if (Module::currentUserCanSeeMotions()) {
            return parent::getStartLayoutView();
        } else {
            return '@app/plugins/dbwv/views/consultation_create_motions';
        }
    }
}
