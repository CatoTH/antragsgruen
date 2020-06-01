<?php

namespace app\plugins\egp;

use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\Consultation;
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

    public static function getConsultationHomePage(Consultation $consultation): string
    {
        Header("Location: https://europeangreens.eu/draft-documents/onlinespring2020");
        die();
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
