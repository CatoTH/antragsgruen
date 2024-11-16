<?php

declare(strict_types=1);

namespace app\models\db\repostory;

use app\components\IMotionStatusFilter;
use app\components\UrlHelper;
use app\models\db\{Consultation, ConsultationMotionType, IMotion, Motion};

class MotionRepository
{
    /** @var array<int|string, array<Motion>> */
    private static array $getReplacedByMotions_cache = [];

    /** @var array<int|string, array<Motion>> */
    private static array $getObsoletedByMotions_cache = [];

    /** @var array<int|string, Motion|null> */
    private static array $motionsById = [];

    private static bool $siteWideCachesInitialized = false;

    public static function flushCaches(): void
    {
        self::$getObsoletedByMotions_cache = [];
        self::$getReplacedByMotions_cache = [];
        self::$siteWideCachesInitialized = false;
    }

    public static function initSiteWideCaches(): void
    {
        if (self::$siteWideCachesInitialized) {
            return;
        }

        self::$getObsoletedByMotions_cache = [];
        self::$getReplacedByMotions_cache = [];

        foreach (UrlHelper::getCurrentSite()->consultations as $consultation) {
            if ($consultation->id === UrlHelper::getCurrentConsultation()->id) {
                $consultation = UrlHelper::getCurrentConsultation();
            }

            foreach ($consultation->motions as $motion) {
                if (!isset(self::$getObsoletedByMotions_cache[$motion->id])) {
                    self::$getObsoletedByMotions_cache[$motion->id] = [];
                }
                if (!isset(self::$getReplacedByMotions_cache[$motion->id])) {
                    self::$getReplacedByMotions_cache[$motion->id] = [];
                }
                if (!isset(self::$motionsById[$motion->id])) {
                    self::$motionsById[$motion->id] = $motion;
                }

                if ($motion->status === IMotion::STATUS_OBSOLETED_BY_MOTION && $motion->statusString > 0) {
                    $replacedBy = intval($motion->statusString);
                    if (!isset(self::$getObsoletedByMotions_cache[$replacedBy])) {
                        self::$getObsoletedByMotions_cache[$replacedBy] = [];
                    }
                    self::$getObsoletedByMotions_cache[$replacedBy][] = $motion;
                }

                if ($motion->parentMotionId > 0) {
                    if (!isset(self::$getReplacedByMotions_cache[$motion->parentMotionId])) {
                        self::$getReplacedByMotions_cache[$motion->parentMotionId] = [];
                    }
                    self::$getReplacedByMotions_cache[$motion->parentMotionId][] = $motion;
                }
            }
        }

        self::$siteWideCachesInitialized = true;
    }

    public static function getMotionByIdOrSlug(Consultation $consultation, int|string $motionSlug): ?Motion
    {
        if (is_numeric($motionSlug) && $motionSlug > 0) {
            $motion = Motion::findOne([
                'consultationId' => $consultation->id,
                'id'             => $motionSlug,
                'slug'           => null
            ]);
        } else {
            $motion = Motion::findOne([
                'consultationId' => $consultation->id,
                'slug'           => $motionSlug
            ]);
        }

        /** @var Motion|null $motion */
        return $motion;
    }

    public static function getReplacedByMotion(Motion $motion): ?Motion
    {
        if (!$motion->parentMotionId) {
            return null;
        }

        if (in_array($motion->parentMotionId, array_keys(self::$motionsById))) {
            return self::$motionsById[$motion->parentMotionId];
        }

        $consultationMotion = $motion->getMyConsultation()->getMotion($motion->parentMotionId);
        if ($consultationMotion) {
            return $consultationMotion;
        }

        /** @var Motion|null $parentMotion */
        $parentMotion = Motion::find()
                        ->where('id = ' . intval($motion->parentMotionId))
                        ->andWhere('status != ' . Motion::STATUS_DELETED)
                        ->one();
        self::$motionsById[$motion->parentMotionId] = $parentMotion;

        return self::$motionsById[$motion->parentMotionId];
    }

    /**
     * @return Motion[]
     */
    public static function getObsoletedByMotionsInAllConsultations(Motion $motion): array
    {
        if (in_array($motion->id, array_keys(self::$getObsoletedByMotions_cache))) {
            return self::$getObsoletedByMotions_cache[(int) $motion->id];
        }

        // The motion list of the consultation we're in is likely already loaded, so let's avoid initializing the objects a second time
        $consultation = UrlHelper::getCurrentConsultation() ?? $motion->getMyConsultation();

        $query = Motion::find()
                       ->where('motion.status = ' . intval(IMotion::STATUS_OBSOLETED_BY_MOTION))
                       ->andWhere('motion.statusString = "' . intval($motion->id) . '"') // hint: in SQL, it neds to be a string, for the index to work
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
        if (in_array($motion->id, array_keys(self::$getReplacedByMotions_cache))) {
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
     * @return Motion[]
     */
    public static function getMotionsForType(ConsultationMotionType $type, IMotionStatusFilter $filter): array
    {
        return $filter->filterMotions($type->motions);
    }
}
