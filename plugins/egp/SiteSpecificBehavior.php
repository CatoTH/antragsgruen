<?php

namespace app\plugins\egp;

use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\Consultation;
use app\models\http\RedirectResponse;
use app\models\http\ResponseInterface;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    public static function getConsultationHomePage(Consultation $consultation): ?ResponseInterface
    {
        /** @var ConsultationSettings $settings */
        $settings = $consultation->getSettings();
        if ($settings->homeRedirectUrl) {
            return new RedirectResponse($settings->homeRedirectUrl);
        } else {
            return null;
        }
    }

    /**
     * @return string[]|IAmendmentNumbering[]
     */
    public static function getCustomAmendmentNumberings(): array
    {
        return [
            EgpAmendmentNumbering::class,
        ];
    }
}
