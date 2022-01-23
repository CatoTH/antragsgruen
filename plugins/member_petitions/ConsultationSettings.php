<?php

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    /** @var string */
    public $organizationId = '';
    /** @var int */
    public $replyDeadline = 14;
    /** @var int */
    public $minDiscussionTime = 21;
    /** @var int */
    public $maxOverallTime = 0;
    /** @var bool */
    public $petitionPage = true;
    /** @var bool */
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

    public function getSpecializedLayoutClass(): string
    {
        return LayoutSettings::class;
    }
}
