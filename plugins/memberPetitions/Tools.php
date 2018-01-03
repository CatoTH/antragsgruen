<?php

namespace app\plugins\memberPetitions;

use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;

class Tools
{
    /**
     * @param Site $site
     * @param User $user
     * @return Consultation[]
     */
    public static function getUserConsultations($site, $user)
    {
        $organizations = $user->getOrganizationIds();
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
        $user    = User::getCurrentUser();
        if (!$user) {
            return $motions;
        }

        foreach ($site->consultations as $consultation) {
            foreach ($consultation->motions as $motion) {
                if ($motion->iAmInitiator() && $motion->isVisibleForAdmins() &&
                    $motion->status != Motion::STATUS_INLINE_REPLY) {
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
        $user    = User::getCurrentUser();
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

    /**
     * @param IMotion $motion
     * @return bool
     */
    public static function canRespondToMotion(IMotion $motion)
    {
        if (!$motion->isVisible() || $motion->status == IMotion::STATUS_PROCESSED) {
            return false;
        }

        $user = User::getCurrentUser();
        return ($user->hasPrivilege($motion->getMyConsultation(), User::PRIVILEGE_CONTENT_EDIT));
    }

    /**
     * @param Motion $motion
     * @return null|Motion
     */
    public static function getMotionResponse(Motion $motion)
    {
        if ($motion->status !== IMotion::STATUS_PROCESSED) {
            return null;
        }
        return Motion::findOne([
            'parentMotionId' => $motion->id,
            'status'         => Motion::STATUS_INLINE_REPLY,
        ]);
    }

    /**
     * @param IMotion $motion
     * @return \DateTime|null
     */
    public static function getMotionResponseDeadline(IMotion $motion)
    {
        if (!$motion->isVisible() || $motion->status === IMotion::STATUS_PROCESSED) {
            return null;
        }
        if (!$motion->dateCreation) {
            return null;
        }
        $date = new \DateTime($motion->dateCreation);
        /** @var ConsultationSettings $settings */
        $settings = $motion->getMyConsultation()->getSettings();
        $date->add(new \DateInterval('P' . $settings->replyDeadline . "D"));

        return $date;
    }

    /**
     * @param IMotion $motion
     * @return bool
     */
    public static function isMotionDeadlineOver(IMotion $motion)
    {
        $deadline = static::getMotionResponseDeadline($motion);
        if (!$deadline) {
            return false;
        }
        return $deadline->getTimestamp() < time();
    }
}
