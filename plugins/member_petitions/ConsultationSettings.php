<?php

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public $organizationId = '';
    public $replyDeadline = 14;
    public $minDiscussionTime = 21;

    /**
     * @return string
     */
    public function getStartLayoutView()
    {
        return '@app/plugins/member_petitions/views/consultation';
    }

    /**
     * @return null|string
     */
    public function getConsultationSidebar()
    {
        return '@app/plugins/member_petitions/views/consultation-sidebar';
    }

    /**
     * @return null|string|LayoutSettings
     */
    public function getSpecializedLayoutClass()
    {
        return LayoutSettings::class;
    }
}
