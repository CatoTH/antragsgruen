<?php

namespace app\memberPetitions;

use app\models\db\Consultation;
use app\models\db\Site;
use app\models\db\User;

class Tools
{
    /**
     * @param $user
     * @return string[]
     */
    private static function getUserOrganizationsWurzelwerk($user)
    {
        return []; // @TODO
    }

    /**
     * Hard-coded test data
     *
     * @param User $user
     * @return string[]
     */
    private static function getUserOrganizationsDebug($user)
    {
        switch ($user->auth) {
            case 'email:testadmin@example.org':
                return ['berlin-id', 'federal-id'];
            case 'email:testuser@example.org':
                return ['bavarian-id', 'federal-id'];
            default:
                return [];
        }
    }

    /**
     * @param User|null $user
     * @return string[]
     */
    public static function getUserOrganizations($user)
    {
        if (!$user) {
            return [];
        }

        if ($user->isWurzelwerkUser()) {
            return static::getUserOrganizationsWurzelwerk($user);
        }
        if (YII_DEBUG) {
            return static::getUserOrganizationsDebug($user);
        }
        return [];
    }

    /**
     * @param Site $site
     * @param User $user
     * @return Consultation[]
     */
    public static function getUserConsultations($site, $user)
    {
        $organizations = static::getUserOrganizations($user);
        $consultations = [];
        foreach ($site->consultations as $consultation) {
            /** @var ConsultationSettings $settings */
            $settings = $consultation->getSettings();
            if (in_array($settings->organizationId, $organizations)) {
                $consultations[] = $consultation;
            }
        }
        return $consultations;
    }
}
