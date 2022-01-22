<?php

namespace app\plugins\openslides;

use app\components\{ExternalPasswordAuthenticatorInterface, UrlHelper};
use app\models\db\{Consultation, Site};
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /** @var PasswordAuthenticator */
    private static $authenticator = null;

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        if (self::$authenticator === null) {
            /** @var SiteSettings $settings */
            $settings = UrlHelper::getCurrentSite()->getSettings();
            $osClient = new OpenslidesClient($settings);
            self::$authenticator = new PasswordAuthenticator($settings, $osClient);
        }
        return self::$authenticator;
    }

    public static function getSiteSettingsClass(Site $site): string
    {
        return SiteSettings::class;
    }

    public static function getConsultationSettingsClass(Consultation $consultation): string
    {
        return ConsultationSettings::class;
    }

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return \Yii::$app->controller->renderPartial(
            '@app/plugins/openslides/views/admin/consultation_settings', ['consultation' => $consultation]
        );
    }
}
