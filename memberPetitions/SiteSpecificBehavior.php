<?php

namespace app\memberPetitions;

use app\models\db\Consultation;
use app\memberPetitions\ConsultationSettings;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    /**
     * @return string
     */
    public static function getConsultationHomePage()
    {
        $controller = \Yii::$app->controller;
        return $controller->render('@app/memberPetitions/views/index');
    }

    /**
     * @return string|ConsultationSettings
     */
    public static function getConsultationSettingsClass()
    {
        return ConsultationSettings::class;
    }

    /**
     * @param Consultation $consultation
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsForm(Consultation $consultation)
    {
        return \Yii::$app->controller->renderPartial(
            '@app/memberPetitions/views/admin/consultation_settings',
            ['consultation' => $consultation]
        );
    }

    /**
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function saveConsultationSettings(Consultation $consultation)
    {
        var_dump($_POST);
        die();
    }
}
