<?php

declare(strict_types=1);

namespace app\models\db\repostory;

use app\components\UrlHelper;
use app\models\db\{Consultation, ConsultationMotionType, IMotion, Motion};

class MotionRepository
{
    /** @var array<int, array<Motion>> */
    private static array $getReplacedByMotions_cache = [];

    /** @var array<int, array<Motion>> */
    private static array $getObsoletedByMotions_cache = [];

    public static function flushCaches(): void
    {
        self::$getObsoletedByMotions_cache = [];
        self::$getReplacedByMotions_cache = [];
    }

    /**
     * @return Motion[]
     */
    public static function getObsoletedByMotionsInAllConsultations(Motion $motion): array
    {
        if (isset(self::$getObsoletedByMotions_cache[(int) $motion->id])) {
            return self::$getObsoletedByMotions_cache[(int) $motion->id];
        }

        // The motion list of the consultation we're in is likely already loaded, so let's avoid initializing the objects a second time
        $consultation = UrlHelper::getCurrentConsultation() ?? $motion->getMyConsultation();

        $query = Motion::find()
                       ->where('motion.status = ' . intval(IMotion::STATUS_OBSOLETED_BY_MOTION))
                       ->andWhere('motion.statusString = ' . intval($motion->id))
                       ->andWhere('motion.consultationId != ' . intval($consultation->id));
        /** @var Motion[] $motionsFromOtherConsultations */
        $motionsFromOtherConsultations = $query->all();

        $motionsFromThisConsultation = array_values(array_filter($consultation->motions, function (Motion $motionSearch) use ($motion): bool {
            return $motionSearch->status === IMotion::STATUS_OBSOLETED_BY_MOTION && intval($motionSearch->statusString) === $motion->id;
        }));

        self::$getObsoletedByMotions_cache[(int) $motion->id] = array_merge($motionsFromThisConsultation, $motionsFromOtherConsultations);

        return self::$getObsoletedByMotions_cache[(int) $motion->id];
    }

    /**
     * @return Motion[]
     */
    public static function getReplacedByMotionsWithinConsultation(Motion $motion): array
    {
        $motions = [];
        if ($motion->getMyConsultation()->hasPreloadedMotionData()) {
            foreach ($motion->getMyConsultation()->motions as $motionSearch) {
                if ($motionSearch->parentMotionId === $motion->id) {
                    $motions[] = $motionSearch;
                }
            }
        } else {
            foreach ($motion->replacedByMotions as $motionSearch) {
                if ($motionSearch->consultationId === $motion->consultationId) {
                    $motions[] = $motionSearch;
                }
            }
        }
        return $motions;
    }

    /**
     * @return Motion[]
     */
    public static function getReplacedByMotionsInAllConsultations(Motion $motion): array
    {
        if (isset(self::$getReplacedByMotions_cache[(int) $motion->id])) {
            return self::$getReplacedByMotions_cache[(int) $motion->id];
        }

        // The motion list of the consultation we're in is likely already loaded, so let's avoid initializing the objects a second time
        $consultation = UrlHelper::getCurrentConsultation() ?? $motion->getMyConsultation();

        $query = Motion::find()
                       ->where('motion.status != ' . intval(IMotion::STATUS_DELETED))
                       ->andWhere('motion.parentMotionId = ' . intval($motion->id))
                       ->andWhere('motion.consultationId != ' . intval($consultation->id));
        /** @var Motion[] $motionsFromOtherConsultations */
        $motionsFromOtherConsultations = $query->all();

        $motionsFromThisConsultation = array_values(array_filter($consultation->motions, function (Motion $motionSearch) use ($motion): bool {
            return $motionSearch->parentMotionId === $motion->id;
        }));

        self::$getReplacedByMotions_cache[(int) $motion->id] = array_merge($motionsFromThisConsultation, $motionsFromOtherConsultations);

        return self::$getReplacedByMotions_cache[(int) $motion->id];
    }

    /**
     * @return Motion[]
     */
    public static function getScreeningMotions(Consultation $consultation): array
    {
        $query = Motion::find();
        $statuses = array_map('intval', $consultation->getStatuses()->getScreeningStatuses());
        $query->where('motion.status IN (' . implode(', ', $statuses) . ')');
        $query->andWhere('motion.consultationId = ' . intval($consultation->id));
        $query->orderBy("dateCreation DESC");
        /** @var Motion[] $motions */
        $motions = $query->all();
        return $motions;
    }

    /**
     * @return Motion[]
     */
    public static function getNewestByConsultation(Consultation $consultation, int $limit = 5): array
    {
        $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());

        $statuteTypes = [];
        foreach ($consultation->motionTypes as $motionType) {
            if ($motionType->amendmentsOnly) {
                $statuteTypes[] = $motionType->id;
            }
        }

        $query = Motion::find();
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
        $query->andWhere('motion.consultationId = ' . intval($consultation->id));
        if (count($statuteTypes) > 0) {
            $query->andWhere('motion.motionTypeId NOT IN (' . implode(', ', $statuteTypes) . ')');
        }
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);
        /** @var Motion[] $motions */
        $motions = $query->all();
        return $motions;
    }

    /**
     * @param int[]|null $ignoredFilters
     *
     * @return Motion[]
     */
    public static function getMotionsForType(ConsultationMotionType $type, ?array $ignoredFilters = null): array
    {
        if ($ignoredFilters === null) {
            $ignoredFilters = $type->getConsultation()->getStatuses()->getUnreadableStatuses();
        }
        $return = [];
        foreach ($type->motions as $motion) {
            if (!in_array($motion->status, $ignoredFilters)) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    /**
     * @param int[]|null $ignoredFilters
     *
     * @return Motion[]
     */
    public static function getMotionsForConsultation(Consultation $consultation, ?array $ignoredFilters = null): array
    {
        if ($ignoredFilters === null) {
            $ignoredFilters = $consultation->getStatuses()->getUnreadableStatuses();
        }
        $return = [];
        foreach ($consultation->motions as $motion) {
            if (!in_array($motion->status, $ignoredFilters)) {
                $return[] = $motion;
            }
        }
        return $return;
    }
}
