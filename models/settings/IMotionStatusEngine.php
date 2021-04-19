<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\IMotion;

class IMotionStatusEngine
{
    /** @var IMotionStatus[] */
    private $allStatusesCache;

    /** @var \app\models\db\Consultation */
    private $consultation;

    public function __construct(\app\models\db\Consultation $consultation)
    {
        $this->consultation = $consultation;

        $statuses = [];
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $statuses = array_merge($statuses, $pluginClass::getAdditionalIMotionStatuses());
        }

        $statuses[] = new IMotionStatus(
            IMotion::STATUS_WITHDRAWN,
            \Yii::t('structure', 'STATUS_WITHDRAWN'),
            \Yii::t('structure', 'STATUSV_WITHDRAWN')
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_DRAFT, \Yii::t('structure', 'STATUS_DRAFT'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_SUBMITTED_UNSCREENED, \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED, \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED_CHECKED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_SUBMITTED_SCREENED, \Yii::t('structure', 'STATUS_SUBMITTED_SCREENED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_ACCEPTED,
            \Yii::t('structure', 'STATUS_ACCEPTED'),
            \Yii::t('structure', 'STATUSV_ACCEPTED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_REJECTED,
            \Yii::t('structure', 'STATUS_REJECTED'),
            \Yii::t('structure', 'STATUSV_REJECTED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MODIFIED_ACCEPTED,
            \Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
            \Yii::t('structure', 'STATUSV_MODIFIED_ACCEPTED')
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
        $statuses[] = new IMotionStatus(IMotion::STATUS_COMPLETED, \Yii::t('structure', 'STATUS_COMPLETED'));
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_REFERRED,
            \Yii::t('structure', 'STATUS_REFERRED'),
            \Yii::t('structure', 'STATUSV_REFERRED')
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_VOTE,
            \Yii::t('structure', 'STATUS_VOTE'),
            \Yii::t('structure', 'STATUSV_VOTE')
        );
        $statuses[] = new IMotionStatus(IMotion::STATUS_PAUSED, \Yii::t('structure', 'STATUS_PAUSED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_MISSING_INFORMATION, \Yii::t('structure', 'STATUS_MISSING_INFORMATION'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_DISMISSED, \Yii::t('structure', 'STATUS_DISMISSED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_COLLECTING_SUPPORTERS, \Yii::t('structure', 'STATUS_COLLECTING_SUPPORTERS'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_DRAFT_ADMIN, \Yii::t('structure', 'STATUS_DRAFT_ADMIN'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_PROCESSED, \Yii::t('structure', 'STATUS_PROCESSED'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_WITHDRAWN_INVISIBLE, \Yii::t('structure', 'STATUS_WITHDRAWN_INVISIBLE'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_OBSOLETED_BY, \Yii::t('structure', 'STATUS_OBSOLETED_BY'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_CUSTOM_STRING, \Yii::t('structure', 'STATUS_CUSTOM_STRING'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_INLINE_REPLY, \Yii::t('structure', 'STATUS_INLINE_REPLY'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_RESOLUTION_PRELIMINARY, \Yii::t('structure', 'STATUS_RESOLUTION_PRELIMINARY'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_RESOLUTION_FINAL, \Yii::t('structure', 'STATUS_RESOLUTION_FINAL'));
        $statuses[] = new IMotionStatus(IMotion::STATUS_MOVED, \Yii::t('structure', 'STATUS_MOVED'));

        $statuses[] = new IMotionStatus(
            IMotion::STATUS_DELETED,
            \Yii::t('structure', 'STATUS_DELETED'),
            \Yii::t('structure', 'STATUSV_DELETED'),
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            \Yii::t('structure', 'STATUS_MERGING_DRAFT_PUBLIC'),
            null,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            \Yii::t('structure', 'STATUS_MERGING_DRAFT_PRIVATE'),
            null,
            true
        );
        $statuses[] = new IMotionStatus(
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            \Yii::t('structure', 'STATUS_PROPOSED_MODIFIED_AMENDMENT'),
            null,
            true
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
        ];
    }

    /**
     * @return int[]
     */
    public function getInvisibleMotionStatuses(bool $withdrawnAreVisible = true): array
    {
        $invisible = [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_WITHDRAWN_INVISIBLE,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
            IMotion::STATUS_PROPOSED_MODIFIED_AMENDMENT,
            IMotion::STATUS_INLINE_REPLY,
        ];
        if (!$this->consultation->getSettings()->screeningMotionsShown) {
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED;
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        }
        if (!$withdrawnAreVisible) {
            $invisible[] = IMotion::STATUS_WITHDRAWN;
            //$invisible[] = IMotion::STATUS_MOVED;
            $invisible[] = IMotion::STATUS_MODIFIED;
            $invisible[] = IMotion::STATUS_MODIFIED_ACCEPTED;
            $invisible[] = IMotion::STATUS_PROCESSED;
        }
        return $invisible;
    }

    /**
     * @return int[]
     */
    public function getInvisibleAmendmentStatuses(bool $withdrawnAreVisible = true): array
    {
        return $this->consultation->getStatuses()->getInvisibleMotionStatuses($withdrawnAreVisible);
    }
}
