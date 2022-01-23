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
            $site = UrlHelper::getCurrentSite();
            /** @var SiteSettings $settings */
            $settings = $site->getSettings();
            $osClient = new OpenslidesClient($settings);
            $syncService = new AutoupdateSyncService();
            $syncService->setRequestData($site);

            self::$authenticator = new PasswordAuthenticator($settings, $osClient, $syncService);
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

    public static function getAllUrlRoutes(string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . '<consultationPath:[\w_-]+>/openslides/autoupdate'] = '/openslides/autoupdate/callback';

        return $urls;
    }
}
