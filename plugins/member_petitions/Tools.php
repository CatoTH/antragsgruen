<?php

namespace app\plugins\member_petitions;

use app\models\db\{Consultation, ConsultationMotionType, IMotion, Motion, MotionSupporter, Site, User};
use app\models\events\MotionEvent;
use app\models\supportTypes\SupportBase;
use app\plugins\member_petitions\notifications\{DiscussionSubmitted, PetitionSubmitted};
use app\components\Tools as DateTools;

class Tools
{
    public static function isPetitionsActive(Consultation $consultation): bool
    {
        /** @var ConsultationSettings $settings */
        $settings = $consultation->getSettings();

        return $settings->petitionPage;
    }

    /**
     * @param Site $site
     * @param User $user
     * @return Consultation[]
     */
    public static function getUserConsultations(Site $site,  ?User $user)
    {
        if (!$user) {
            return [];
        }

        $organizations = $user->getMyOrganizationIds();
        $consultations = [];
        foreach ($site->consultations as $consultation) {
            /** @var ConsultationSettings $settings */
            $settings = $consultation->getSettings();

            if ($settings->maintenanceMode && !$user->hasPrivilege($consultation, User::PRIVILEGE_ANY)) {
                continue;
            }

            if (in_array($settings->organizationId, $organizations)) {
                $consultations[] = $consultation;
            }
        }
        return $consultations;
    }

    public static function getDiscussionType(Consultation $consultation): ?ConsultationMotionType
    {
        foreach ($consultation->motionTypes as $motionType) {
            if ($motionType->getMotionSupporterSettings()->type !== SupportBase::COLLECTING_SUPPORTERS) {
                return $motionType;
            }
        }
        return null;
    }

    public static function getPetitionType(Consultation $consultation): ?ConsultationMotionType
    {
        foreach ($consultation->motionTypes as $motionType) {
            if ($motionType->getMotionSupporterSettings()->type === SupportBase::COLLECTING_SUPPORTERS) {
                return $motionType;
            }
        }
        return null;
    }

    public static function isConsultationFullyConfigured(Consultation $consultation): bool
    {
        return (static::getPetitionType($consultation) !== null && static::getDiscussionType($consultation) !== null);
    }

    /**
     * @param Consultation[] $consultations
     * @return Motion[]
     */
    public static function getAllMotions($consultations)
    {
        $all = [];
        foreach ($consultations as $consultation) {
            if (!Tools::isConsultationFullyConfigured($consultation)) {
                continue;
            }
            $all = array_merge($all, static::getPetitionType($consultation)->getVisibleMotions(false));
            $all = array_merge($all, static::getMotionsCollecting($consultation));
            $all = array_merge($all, static::getDiscussionType($consultation)->getVisibleMotions(false));
            $all = array_merge($all, static::getMotionsAnswered($consultation));
        }
        return $all;
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsInDiscussion(Consultation $consultation)
    {
        $motions = Tools::getDiscussionType($consultation)->getVisibleMotions(false);
        return array_filter($motions, function (Motion $motion) {
            return ($motion->status == IMotion::STATUS_SUBMITTED_SCREENED);
        });
    }

    /**
     * @param Consultation[] $consultations
     * @return Motion[]
     */
    public static function getAllMotionsInDiscussion($consultations)
    {
        $all = [];
        foreach ($consultations as $consultation) {
            if (!Tools::isConsultationFullyConfigured($consultation)) {
                continue;
            }
            $all = array_merge($all, static::getMotionsInDiscussion($consultation));
        }
        return $all;
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsAnswered(Consultation $consultation)
    {
        $motions = Tools::getPetitionType($consultation)->getVisibleMotions(true); // true: wg. processed
        return array_filter($motions, function (Motion $motion) {
            return ($motion->status == IMotion::STATUS_PROCESSED);
        });
    }

    /**
     * @param Consultation[] $consultations
     * @return Motion[]
     */
    public static function getAllMotionsAnswered($consultations)
    {
        $all = [];
        foreach ($consultations as $consultation) {
            if (!Tools::isConsultationFullyConfigured($consultation)) {
                continue;
            }
            $all = array_merge($all, static::getMotionsAnswered($consultation));
        }
        return $all;
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsUnanswered(Consultation $consultation)
    {
        $motions = Tools::getPetitionType($consultation)->getVisibleMotions(false);
        return array_filter($motions, function (Motion $motion) {
            return ($motion->status != IMotion::STATUS_PROCESSED);
        });
    }

    /**
     * @param Consultation[] $consultations
     * @return Motion[]
     */
    public static function getAllMotionsUnanswered($consultations)
    {
        $all = [];
        foreach ($consultations as $consultation) {
            if (!Tools::isConsultationFullyConfigured($consultation)) {
                continue;
            }
            $all = array_merge($all, static::getMotionsUnanswered($consultation));
        }
        return $all;
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getMotionsCollecting(Consultation $consultation)
    {
        $motions = Tools::getPetitionType($consultation)->motions; // Collecting phase is not visible by default
        return array_filter($motions, function (Motion $motion) {
            return ($motion->status == IMotion::STATUS_COLLECTING_SUPPORTERS);
        });
    }

    /**
     * @param Consultation[] $consultations
     * @return Motion[]
     */
    public static function getAllMotionsCollection($consultations)
    {
        $all = [];
        foreach ($consultations as $consultation) {
            if (!Tools::isConsultationFullyConfigured($consultation)) {
                continue;
            }
            $all = array_merge($all, static::getMotionsCollecting($consultation));
        }
        return $all;
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
            if (!static::isPetitionsActive($consultation)) {
                continue;
            }
            foreach ($consultation->motions as $motion) {
                if ($motion->status === Motion::STATUS_INLINE_REPLY) {
                    continue;
                }
                if (count($motion->getVisibleReplacedByMotions()) > 0) {
                    continue;
                }
                if ($motion->iAmInitiator() && $motion->isVisibleForAdmins()) {
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
                    if ($supporter->userId == $user->id && !in_array($motion, $motions, true)) {
                        $motions[] = $motion;
                    }
                }
            }
        }

        return $motions;
    }

    /**
     * @param IMotion $motion
     * @return bool
     */
    public static function canRespondToPetition(IMotion $motion)
    {
        $consultation = $motion->getMyConsultation();

        // If the phase system is active, then it is restricted to the petition type.
        // If not, then it is active for all motion types, IF the option has beed activated
        if (Tools::isPetitionsActive($consultation)) {
            $typePetition = Tools::getPetitionType($motion->getMyConsultation());
            if ($motion->getMyMotionType()->id !== $typePetition->id) {
                return false;
            }
        } else {
            /** @var ConsultationSettings $settings */
            $settings = $consultation->getSettings();
            if (!$settings->canAlwaysRespond) {
                return false;
            }
        }

        if (!$motion->isVisible() || $motion->status === IMotion::STATUS_PROCESSED) {
            return false;
        }

        $user = User::getCurrentUser();
        return ($user && $user->hasPrivilege($motion->getMyConsultation(), User::PRIVILEGE_CONTENT_EDIT));
    }

    public static function getMotionResponse(Motion $motion): ?Motion
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
    public static function getPetitionResponseDeadline(IMotion $motion): ?\DateTime
    {
        $typePetition = Tools::getPetitionType($motion->getMyConsultation());
        if ($motion->getMyMotionType()->id !== $typePetition->id) {
            return null;
        }
        if (!$motion->isVisible() || in_array($motion->status, [IMotion::STATUS_PROCESSED, IMotion::STATUS_PAUSED])) {
            return null;
        }

        $dateStr = ($motion->datePublication ? $motion->datePublication : $motion->dateCreation);
        try {
            $date = new \DateTime($dateStr);
            /** @var ConsultationSettings $settings */
            $settings = $motion->getMyConsultation()->getSettings();
            $date->add(new \DateInterval('P' . $settings->replyDeadline . "D"));

            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param IMotion $motion
     * @return bool
     * @throws \Exception
     */
    public static function isMotionDeadlineOver(IMotion $motion)
    {
        $deadline = static::getPetitionResponseDeadline($motion);
        if (!$deadline) {
            return false;
        }
        return $deadline->getTimestamp() < time();
    }

    /**
     * @param IMotion $motion
     * @return \DateTime|null
     */
    public static function getDiscussionUntil(IMotion $motion): ?\DateTime
    {
        $typeDiscussion = Tools::getDiscussionType($motion->getMyConsultation());
        if ($motion->getMyMotionType()->id !== $typeDiscussion->id) {
            return null;
        }
        if (!$motion->isVisible() || $motion->status !== IMotion::STATUS_SUBMITTED_SCREENED) {
            return null;
        }
        $dateStr = ($motion->datePublication ? $motion->datePublication : $motion->dateCreation);
        try {
            $date = new \DateTime($dateStr);
            /** @var ConsultationSettings $settings */
            $settings = $motion->getMyConsultation()->getSettings();
            $date->add(new \DateInterval('P' . $settings->minDiscussionTime . "D"));

            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function isDiscussion(IMotion $motion)
    {
        return $motion->getMyMotionType()->id === Tools::getDiscussionType($motion->getMyConsultation())->id;
    }

    public static function isPetition(IMotion $motion)
    {
        return $motion->getMyMotionType()->id === Tools::getPetitionType($motion->getMyConsultation())->id;
    }

    /**
     * @param Motion $motion
     * @return \DateTime|null
     * @throws \Exception
     */
    public static function getMotionOverallLimit(Motion $motion)
    {
        /** @var ConsultationSettings $settings */
        $settings = $motion->getMyConsultation()->getSettings();
        if (!($settings->maxOverallTime > 0)) {
            return null;
        }

        if (!$motion->isReadable()) {
            // Withdrawn, finished discussions etc.
            return null;
        }
        if (Tools::isDiscussion($motion) && in_array($motion->status, [
                Motion::STATUS_MODIFIED,
                Motion::STATUS_PAUSED,
            ])) {
            return null;
        }
        if (Tools::isPetition($motion) && in_array($motion->status, [
                Motion::STATUS_SUBMITTED_SCREENED,
                Motion::STATUS_PROCESSED,
                Motion::STATUS_INLINE_REPLY,
                Motion::STATUS_PAUSED,
            ])) {
            return null;
        }

        $baseMotion = $motion;
        $recLimit   = 100;
        while ($baseMotion->replacedMotion && $recLimit > 0) {
            $baseMotion = $baseMotion->replacedMotion;
            $recLimit--;
        }

        $dateStr = ($baseMotion->datePublication ? $baseMotion->datePublication : $baseMotion->dateCreation);
        $date    = new \DateTime($dateStr);
        $date->add(new \DateInterval('P' . $settings->maxOverallTime . "D"));

        return $date;
    }

    /**
     * @param Motion $motion
     * @return bool
     * @throws \Exception
     */
    public static function isMotionOverallDeadlineOver(Motion $motion)
    {
        $deadline = static::getMotionOverallLimit($motion);
        if (!$deadline) {
            return false;
        }
        return $deadline->getTimestamp() < time();
    }

    /**
     * @param IMotion $motion
     * @return int
     */
    public static function getMotionPhaseNumber(IMotion $motion)
    {
        if ($motion->getMyMotionType()->id === Tools::getDiscussionType($motion->getMyConsultation())->id) {
            return 1; // In Discussion
        } elseif ($motion->getMyMotionType()->id === Tools::getPetitionType($motion->getMyConsultation())->id) {
            if ($motion->status === IMotion::STATUS_COLLECTING_SUPPORTERS || $motion->status === IMotion::STATUS_PAUSED) {
                return 2; // Collecting
            } elseif ($motion->status === IMotion::STATUS_PROCESSED) {
                return 4; // Answered
            } else {
                return 3; // Waiting for answer
            }
        } else {
            return 0;
        }
    }

    /**
     * @param IMotion $motion
     * @return bool
     * @throws \Exception
     */
    public static function isDiscussionUntilOver(IMotion $motion)
    {
        $deadline = static::getDiscussionUntil($motion);
        if (!$deadline) {
            return false;
        }
        return $deadline->getTimestamp() < time();
    }

    /**
     * @param MotionEvent $event
     * @throws \app\models\exceptions\FormError
     */
    public static function onMerged(MotionEvent $event)
    {
        $motion = $event->motion;
        if ($motion->motionTypeId !== static::getDiscussionType($motion->getMyConsultation())->id) {
            return;
        }

        $motion->setMotionType(static::getPetitionType($motion->getMyConsultation()));

        $motion->status          = Motion::STATUS_COLLECTING_SUPPORTERS;
        $motion->datePublication = null;
        $motion->save();

        if (count($motion->getInitiators()) === 0 && $motion->replacedMotion) {
            foreach ($motion->replacedMotion->getInitiators() as $initiator) {
                $newInitiator = new MotionSupporter();
                $newInitiator->setAttributes($initiator->getAttributes(), false);
                $newInitiator->dateCreation = date('Y-m-d H:i:s');
                $newInitiator->id           = null;
                $newInitiator->motionId     = $motion->id;
                $newInitiator->save();
            }
        }
    }

    /**
     * @param MotionEvent $event
     * @throws \app\models\exceptions\MailNotSent
     * @throws \app\models\exceptions\ServerConfiguration
     */
    public static function onPublishedFirst(MotionEvent $event)
    {
        $motion = $event->motion;
        if (static::isPetitionsActive($motion->getMyConsultation())) {
            if ($motion->motionTypeId === static::getDiscussionType($motion->getMyConsultation())->id) {
                new DiscussionSubmitted($motion);
            }
            if ($motion->motionTypeId === static::getPetitionType($motion->getMyConsultation())->id) {
                new PetitionSubmitted($motion);
            }
        }
    }

    /**
     * @param IMotion $motion
     * @return int
     */
    public static function getMotionTimestamp(IMotion $motion)
    {
        if ($motion->datePublication) {
            return DateTools::dateSql2timestamp($motion->datePublication);
        } elseif ($motion->dateCreation) {
            return DateTools::dateSql2timestamp($motion->dateCreation);
        } else {
            return 0;
        }
    }
}
