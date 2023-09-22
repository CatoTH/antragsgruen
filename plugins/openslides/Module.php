<?php

namespace app\plugins\openslides;

use app\components\{ExternalPasswordAuthenticatorInterface, RequestContext, UrlHelper};
use app\models\db\{Consultation, Site};
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    private static ?PasswordAuthenticator $authenticator = null;

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

    /**
     * @return class-string<\app\models\settings\Consultation>
     */
    public static function getConsultationSettingsClass(Consultation $consultation): string
    {
        return ConsultationSettings::class;
    }

    public static function getConsultationExtraSettingsForm(Consultation $consultation): string
    {
        return RequestContext::getController()->renderPartial(
            '@app/plugins/openslides/views/admin/consultation_settings', ['consultation' => $consultation]
        );
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        $urls = parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld);

        $urls[$dom . 'openslides/autoupdate'] = '/openslides/autoupdate/callback';

        return $urls;
    }
}
