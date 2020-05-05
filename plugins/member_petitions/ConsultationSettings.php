<?php

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public $organizationId = '';
    public $replyDeadline = 14;
    public $minDiscussionTime = 21;
    public $maxOverallTime = 0;
    public $petitionPage = true;
    public $canAlwaysRespond = false;

    public function getStartLayoutView(): string
    {
        if ($this->petitionPage) {
            return '@app/plugins/member_petitions/views/consultation';
        } else {
            return parent::getStartLayoutView();
        }
    }

    public function getConsultationSidebar(): ?string
    {
        if ($this->petitionPage) {
            return '@app/plugins/member_petitions/views/consultation-sidebar';
        } else {
            return '@app/views/consultation/sidebar';
        }
    }

    /**
     * @return null|string|LayoutSettings
     */
    public function getSpecializedLayoutClass(): ?string
    {
        return LayoutSettings::class;
    }
}
