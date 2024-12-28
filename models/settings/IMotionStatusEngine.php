<?php

declare(strict_types=1);

namespace app\models\settings;

use app\components\MotionSorter;
use app\models\db\{Amendment, IMotion};

class IMotionStatusEngine
{
    /** @var IMotionStatus[] */
    private array $allStatusesCache;

    public function __construct(
        private readonly \app\models\db\Consultation $consultation
    ) {
        $statuses = [];
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $statuses = array_merge($statuses, $pluginClass::getAdditionalIMotionStatuses($this->consultation));
        }

        $statuses[] = new IMotionStatus(
            IMotion::STATUS_WITHDRAWN,
            \Yii::t('structure', 'STATUS_WITHDRAWN'),
            \Yii::t('structure', 'STATUSV_WITHDRAWN')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_DRAFT,
            \Yii::t('structure', 'STATUS_DRAFT'),
            null,
            false,
            true
        );

        $statuses[] = new IMotionStatus(
            IMotion::STATUS_SUBMITTED_UNSCREENED,
            \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED'),
            null,
            false,
            !$this->consultation->getSettings()->screeningMotionsShown
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED,
            \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED_CHECKED'),
            null,
            false,
            !$this->consultation->getSettings()->screeningMotionsShown
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_SUBMITTED_SCREENED, \Yii::t('structure', 'STATUS_SUBMITTED_SCREENED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_ACCEPTED,
            \Yii::t('structure', 'STATUS_ACCEPTED'),
            \Yii::t('structure', 'STATUSV_ACCEPTED'),
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_ACCEPTED_AMEND')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_REJECTED,
            \Yii::t('structure', 'STATUS_REJECTED'),
            \Yii::t('structure', 'STATUSV_REJECTED'),
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_REJECTED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MODIFIED_ACCEPTED,
            \Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
            \Yii::t('structure', 'STATUSV_MODIFIED_ACCEPTED'),
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MODIFIED,
            \Yii::t('structure', 'STATUS_MODIFIED'),
            \Yii::t('structure', 'STATUSV_MODIFIED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_ADOPTED,
            \Yii::t('structure', 'STATUS_ADOPTED'),
            \Yii::t('structure', 'STATUSV_ADOPTED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_QUORUM_MISSED,
            \Yii::t('structure', 'STATUS_QUORUM_MISSED'),
            \Yii::t('structure', 'STATUS_QUORUM_MISSED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_QUORUM_REACHED,
            \Yii::t('structure', 'STATUS_QUORUM_REACHED'),
            \Yii::t('structure', 'STATUS_QUORUM_REACHED')
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_COMPLETED, \Yii::t('structure', 'STATUS_COMPLETED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_REFERRED,
            \Yii::t('structure', 'STATUS_REFERRED'),
            \Yii::t('structure', 'STATUSV_REFERRED'),
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_REFERRED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_VOTE,
            \Yii::t('structure', 'STATUS_VOTE'),
            \Yii::t('structure', 'STATUSV_VOTE'),
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_VOTE')
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_PAUSED, \Yii::t('structure', 'STATUS_PAUSED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_MISSING_INFORMATION, \Yii::t('structure', 'STATUS_MISSING_INFORMATION'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_DISMISSED, \Yii::t('structure', 'STATUS_DISMISSED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            \Yii::t('structure', 'STATUS_COLLECTING_SUPPORTERS'),
            null,
            false,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_DRAFT_ADMIN,
            \Yii::t('structure', 'STATUS_DRAFT_ADMIN'),
            null,
            false,
            true
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_PROCESSED, \Yii::t('structure', 'STATUS_PROCESSED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_WITHDRAWN_INVISIBLE,
            \Yii::t('structure', 'STATUS_WITHDRAWN_INVISIBLE'),
            null,
            false,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_OBSOLETED_BY_MOTION,
            \Yii::t('structure', 'STATUS_OBSOLETED_BY_MOTION'),
            null,
            false,
            !$this->consultation->getSettings()->obsoletedByMotionsShown,
            true,
            false,
            \Yii::t('structure', 'PROPOSED_OBSOLETED_BY_AMEND')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_OBSOLETED_BY_AMENDMENT,
            \Yii::t('structure', 'STATUS_OBSOLETED_BY_AMEND'),
            null,
            false,
            !$this->consultation->getSettings()->obsoletedByMotionsShown,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_OBSOLETED_BY_MOT')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_CUSTOM_STRING,
            \Yii::t('structure', 'STATUS_CUSTOM_STRING'),
            null,
            false,
            false,
            true,
            true,
            \Yii::t('structure', 'PROPOSED_CUSTOM_STRING')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_INLINE_REPLY,
            \Yii::t('structure', 'STATUS_INLINE_REPLY'),
            null,
            false,
            true
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_RESOLUTION_PRELIMINARY, \Yii::t('structure', 'STATUS_RESOLUTION_PRELIMINARY'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_RESOLUTION_FINAL, \Yii::t('structure', 'STATUS_RESOLUTION_FINAL'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_MOVED, \Yii::t('structure', 'STATUS_MOVED'));

        $statuses[] = new IMotionStatus(
            IMotion::STATUS_DELETED,
            \Yii::t('structure', 'STATUS_DELETED'),
            \Yii::t('structure', 'STATUSV_DELETED'),
            true,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            \Yii::t('structure', 'STATUS_MERGING_DRAFT_PUBLIC'),
            null,
            true,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            \Yii::t('structure', 'STATUS_MERGING_DRAFT_PRIVATE'),
            null,
            true,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            \Yii::t('structure', 'STATUS_PROPOSED_MODIFIED_AMENDMENT'),
            null,
            true,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_PROPOSED_MODIFIED_MOTION,
            \Yii::t('structure', 'STATUS_PROPOSED_MODIFIED_MOTION'),
            null,
            true,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION,
            \Yii::t('structure', 'STATUS_STATUS_PROPOSED_MOVE_TO_OTHER_MOTION'),
            null,
            false,
            true,
            false,
            true,
            \Yii::t('structure', 'PROPOSED_MOVE_TO_OTHER_MOTION')
        );

        $this->allStatusesCache = $statuses;
    }

    /**
     * @return IMotionStatus[]
     */
    public function getAllStatuses(): array
    {
        return $this->allStatusesCache;
    }

    /**
     * @return string[]
     */
    public function getStatusesAsVerbs(): array
    {
        $statuses = [];

        foreach ($this->allStatusesCache as $status) {
            $statuses[$status->id] = $status->nameVerb ?: $status->name;
        }

        return $statuses;
    }

    /**
     * @return int[]
     */
    public function getStatusesInvisibleForAdmins(): array
    {
        $statuses = [];

        foreach ($this->getAllStatuses() as $status) {
            if ($status->adminInvisible) {
                $statuses[] = $status->id;
            }
        }

        return $statuses;
    }

    /**
     * @return string[]
     */
    public function getStatusNames(bool $includeAdminInvisibles = false): array
    {
        $statuses = [];

        foreach ($this->getAllStatuses() as $status) {
            if ($includeAdminInvisibles || !$status->adminInvisible) {
                $statuses[$status->id] = $status->name;
            }
        }

        return $statuses;
    }

    public function getStatusName(int $statusId): string
    {
        foreach ($this->getAllStatuses() as $status) {
            if ($status->id === $statusId) {
                return $status->name;
            }
        }
        return '???';
    }

    /**
     * @return string[]
     */
    public function getStatusNamesVisibleForAdmins(): array
    {
        $names     = [];
        $invisible = $this->getStatusesInvisibleForAdmins();
        foreach ($this->getStatusNames() as $id => $name) {
            if (!in_array($id, $invisible)) {
                $names[$id] = $name;
            }
        }

        return $names;
    }

    /**
     * @return int[]
     */
    public function getUnreadableStatuses(): array
    {
        return [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            IMotion::STATUS_PROPOSED_MODIFIED_MOTION,
        ];
    }

    /**
     * Used to decide if "submitted on" or "created on" is shown on the motion page. Mostly relevant for the collection phase.
     * @return int[]
     */
    public function getNotYetSubmittedStatuses(): array
    {
        return [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
        ];
    }

    /**
     * @return int[]
     */
    public function getScreeningStatuses(): array
    {
        return [
            IMotion::STATUS_SUBMITTED_UNSCREENED,
            IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED
        ];
    }

    /**
     * @return int[]
     */
    public function getInvisibleMotionStatuses(bool $withdrawnAreVisible = true): array
    {
        $invisible = [];
        foreach ($this->allStatusesCache as $status) {
            if ($status->userInvisible) {
                $invisible[] = $status->id;
            }
        }
        if (!$withdrawnAreVisible) {
            $invisible[] = IMotion::STATUS_WITHDRAWN;
        }
        return $invisible;
    }

    /**
     * @return int[]
     */
    public function getInvisibleAmendmentStatuses(bool $withdrawnAreVisible = true): array
    {
        return $this->getInvisibleMotionStatuses($withdrawnAreVisible);
    }

    public function getAmendmentStatusesUnselectableForMerging(): array
    {
        $invisible = [];
        foreach ($this->allStatusesCache as $status) {
            if ($status->userInvisible && $status->id !== IMotion::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
                $invisible[] = $status->id;
            }
        }
        return $invisible;
    }

    public function getStatusesInvisibleForProposedProcedure(): array
    {
        return $this->getAmendmentStatusesUnselectableForMerging();
    }

    /**
     * @return int[]
     */
    public function getStatusesMarkAsDoneOnRewriting(): array
    {
        return [
            IMotion::STATUS_PROCESSED,
            IMotion::STATUS_ACCEPTED,
            IMotion::STATUS_REJECTED,
            IMotion::STATUS_MODIFIED_ACCEPTED,
        ];
    }

    /**
     * @return string[]
     */
    public function getVotingStatuses(): array
    {
        return [
            IMotion::STATUS_VOTE => \Yii::t('structure', 'STATUS_VOTE'),
            IMotion::STATUS_ACCEPTED => \Yii::t('structure', 'STATUS_ACCEPTED'),
            IMotion::STATUS_REJECTED => \Yii::t('structure', 'STATUS_REJECTED'),
            IMotion::STATUS_QUORUM_MISSED => \Yii::t('structure', 'STATUS_QUORUM_MISSED'),
            IMotion::STATUS_QUORUM_REACHED => \Yii::t('structure', 'STATUS_QUORUM_REACHED'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getMotionProposedProcedureStatuses(): array
    {
        $statuses = [];
        foreach ($this->allStatusesCache as $status) {
            if ($status->motionProposedProcedureStatus) {
                $statuses[$status->id] = $status->proposedProcedureName;
            }
        }
        return $statuses;
    }

    /**
     * @return array<int, string>
     */
    public function getAmendmentProposedProcedureStatuses(): array
    {
        $statuses = [];
        foreach ($this->allStatusesCache as $status) {
            if ($status->amendmentProposedProcedureStatus) {
                $statuses[$status->id] = $status->proposedProcedureName;
            }
        }
        return $statuses;
    }

    public function getProposedProcedureStatusName(int $statusId): ?string
    {
        foreach ($this->allStatusesCache as $status) {
            if ($status->id === $statusId) {
                return $status->proposedProcedureName;
            }
        }
        return null;
    }

    /**
     * @param IMotion[] $imotions
     * @param int[] $statuses
     *
     * @return IMotion[]
     */
    public static function filterIMotionsByAllowedStatuses(array $imotions, array $statuses, bool $sort = false): array
    {
        $imotions = array_values(array_filter($imotions, function (IMotion $imotion) use ($statuses) {
            return in_array($imotion->status, $statuses);
        }));
        if ($sort && count($imotions) > 0) {
            $imotions = MotionSorter::getSortedIMotionsFlat($imotions[0]->getMyConsultation(), $imotions);
        }
        return $imotions;
    }

    /**
     * @param IMotion[] $imotions
     * @param int[] $statuses
     *
     * @return IMotion[]
     */
    public static function filterIMotionsByForbiddenStatuses(array $imotions, array $statuses, bool $sort = false): array
    {
        $imotions = array_values(array_filter($imotions, function (IMotion $imotion) use ($statuses) {
            return !in_array($imotion->status, $statuses);
        }));
        if ($sort && count($imotions) > 0) {
            $imotions = MotionSorter::getSortedIMotionsFlat($imotions[0]->getMyConsultation(), $imotions);
        }
        return $imotions;
    }

    /**
     * @param Amendment[] $amendments
     * @param int[] $statuses
     *
     * @return Amendment[]
     */
    public static function filterAmendmentsByAllowedStatuses(array $amendments, array $statuses, bool $sort = false): array
    {
        $amendments = array_values(array_filter($amendments, function (IMotion $amendments) use ($statuses) {
            return in_array($amendments->status, $statuses);
        }));
        if ($sort && count($amendments) > 0) {
            $amendments = MotionSorter::getSortedAmendments($amendments[0]->getMyConsultation(), $amendments);
        }
        return $amendments;
    }

    /**
     * @param Amendment[] $amendments
     * @param int[] $statuses
     *
     * @return Amendment[]
     */
    public static function filterAmendmentsByForbiddenStatuses(array $amendments, array $statuses, bool $sort = false): array
    {
        $amendments = array_values(array_filter($amendments, function (IMotion $amendments) use ($statuses) {
            return !in_array($amendments->status, $statuses);
        }));
        if ($sort && count($amendments) > 0) {
            $amendments = MotionSorter::getSortedAmendments($amendments[0]->getMyConsultation(), $amendments);
        }
        return $amendments;
    }
}
