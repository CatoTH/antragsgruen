<?php

declare(strict_types=1);

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public string $organizationId = '';
    public int $replyDeadline = 14;
    public int $minDiscussionTime = 21;
    public int $maxOverallTime = 0;
    public bool $petitionPage = true;
    public bool $canAlwaysRespond = false;

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
     * @return class-string<LayoutSettings>
     */
    public function getSpecializedLayoutClass(): string
    {
        return LayoutSettings::class;
    }
}
