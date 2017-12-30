<?php

namespace app\memberPetitions;

use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\Motion;
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
            case 'email:fixeddata@example.org':
                return ['bavarian-id', 'federal-id'];
            case 'email:fixedadmin@example.org':
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

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsAnswered(Consultation $consultation)
    {
        return array_filter($consultation->motions, function (Motion $motion) {
            return ($motion->status == IMotion::STATUS_PROCESSED);
        });
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsUnanswered(Consultation $consultation)
    {
        return array_filter($consultation->getVisibleMotions(), function (Motion $motion) {
            return ($motion->status != IMotion::STATUS_PROCESSED);
        });
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsCollecting(Consultation $consultation)
    {
        return array_filter($consultation->motions, function (Motion $motion) {
            return ($motion->status == IMotion::STATUS_COLLECTING_SUPPORTERS);
        });
    }

    /**
     * @param Site $site
     * @return Motion[]
     */
    public static function getMyMotions(Site $site)
    {
        $motions = [];
        $user = User::getCurrentUser();
        if (!$user) {
            return $motions;
        }

        foreach ($site->consultations as $consultation) {
            foreach ($consultation->motions as $motion) {
                if ($motion->iAmInitiator()) {
                    $motions[] = $motion;
                }
            }
        }

        return $motions;
    }

    /**
     * @param Site $site
     * @return Motion[]
     */
    public static function getSupportedMotions(Site $site)
    {
        $motions = [];
        $user = User::getCurrentUser();
        if (!$user) {
            return $motions;
        }

        foreach ($site->consultations as $consultation) {
            foreach ($consultation->motions as $motion) {
                foreach ($motion->getSupporters() as $supporter) {
                    if ($supporter->userId == $user->id) {
                        $motions[] = $motion;
                    }
                }
            }
        }

        return array_unique($motions);
    }
}
