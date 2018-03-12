<?php

namespace app\plugins\memberPetitions;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     */
    protected static function getMotionUrlRoutes()
    {
        return [
            'write-petition-response' => 'memberPetitions/frontend/write-response',
        ];
    }

    /**
     * @param Site $site
     * @return null|DefaultBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior($site)
    {
        return SiteSpecificBehavior::class;
    }

    /**
     * @param Consultation $consultation
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass($consultation)
    {
        return ConsultationSettings::class;
    }

    /**
     * @param string $category
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getMessagePath($category)
    {
        if ($category === 'memberpetitions') {
            return '@app/plugins/memberPetitions/messages/';
        } else {
            return null;
        }
    }
}
