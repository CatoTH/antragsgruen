<?php

namespace app\plugins\egp;

use app\models\db\Consultation;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    public static function getConsultationHomePage(Consultation $consultation): string
    {
        Header("Location: https://europeangreens.eu/draft-documents/onlinespring2020");
        die();
    }
}
