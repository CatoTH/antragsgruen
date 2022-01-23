<?php

namespace app\plugins\european_youth_forum;

use app\models\db\{Consultation, User};
use app\models\layoutHooks\Hooks;
use app\models\settings\VotingData;

class LayoutHooks extends Hooks
{
    public function getVotingAlternativeAdminResults(?string $before, Consultation $consultation): ?string
    {
        return (string)file_get_contents(__DIR__ . '/views/voting-result-admin.vue.php');
    }

    public function getVotingAlternativeUserResults(?array $before, VotingData $votingData): ?array
    {
        return require(__DIR__ . '/views/voting-result-user.php');
    }

    public function getFormattedUsername(string $before, User $user): string
    {
        return trim($user->organization) !== '' ? $user->organization : $user->name;
    }
}
