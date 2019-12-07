<?php

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public $organizationId = '';
    public $replyDeadline = 14;
    public $minDiscussionTime = 21;
    public $maxOverallTime = 0;

    public function getStartLayoutView(): string
    {
        return '@app/plugins/member_petitions/views/consultation';
    }

    public function getConsultationSidebar(): ?string
    {
        return '@app/plugins/member_petitions/views/consultation-sidebar';
    }

    /**
     * @return null|string|LayoutSettings
     */
    public function getSpecializedLayoutClass(): ?string
    {
        return LayoutSettings::class;
    }
}
