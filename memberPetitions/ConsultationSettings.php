<?php

namespace app\memberPetitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public $organizationId = '';
    public $replyDeadline = 14;

    /**
     * @return string
     */
    public function getStartLayoutView()
    {
        return '@app/memberPetitions/views/consultation';
    }

    /**
     * @return null|string
     */
    public function getConsultationSidebar()
    {
        return '@app/memberPetitions/views/consultation-sidebar';
    }
}
