<?php

namespace app\plugins\memberPetitions;

use app\models\db\Consultation;
use app\models\policies\IPolicy;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    /**
     * @return string|Permissions
     */
    public static function getPermissionsClass()
    {
        return Permissions::class;
    }

    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies()
    {
        return [
            MotionPolicy::class,
        ];
    }

    /**
     * @return string
     */
    public static function hasSiteHomePage()
    {
        return true;
    }

    /**
     * @return string
     */
    public static function getSiteHomePage()
    {
        $controller = \Yii::$app->controller;
        return $controller->render('@app/plugins/memberPetitions/views/index');
    }

    /**
     * @param Consultation $consultation
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsForm(Consultation $consultation)
    {
        return \Yii::$app->controller->renderPartial(
            '@app/plugins/memberPetitions/views/admin/consultation_settings',
            ['consultation' => $consultation]
        );
    }
}
