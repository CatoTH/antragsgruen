<?php

namespace app\plugins\european_youth_forum;

use app\models\db\Consultation;
use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    public function getVotingAlternativeAdminResults(?string $before, Consultation $consultation): ?string
    {
        return file_get_contents(__DIR__ . '/views/voting-result-admin.php');
    }
}
