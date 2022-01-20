<?php

namespace app\plugins\openslides;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\Site;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /** @var PasswordAuthenticator */
    private static $authenticator = null;

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        if (static::$authenticator === null) {
            $settings = UrlHelper::getCurrentSite()->getSettings();
            static::$authenticator = new PasswordAuthenticator($settings);
        }
        return static::$authenticator;
    }

    /**
     * @return string|SiteSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSettingsClass(Site $site)
    {
        return SiteSettings::class;
    }

    /**
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass(Consultation $consultation): ?string
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
