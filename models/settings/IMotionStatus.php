<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\IMotion;

class IMotionStatus
{
    /**
     * For plugin-defined IDs, use IDs of 100+
     * @var int
     */
    public $id;

    /**
     * e.g. "published"
     * @var string
     */
    public $name;

    /**
     * e.g. "publish"
     * @var string|null
     */
    public $nameVerb;

    /**
     * @var bool
     */
    public $adminInvisible;

    public function __construct(int $id, string $name, ?string $nameVerb = null, ?bool $adminInvisible = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->nameVerb = $nameVerb;
        $this->adminInvisible = $adminInvisible;
    }

    private static $allStatusesCache = null;

    /**
     * @return IMotionStatus[]
     */
    public static function getAllStatuses(): array
    {
        if (static::$allStatusesCache === null) {
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

            static::$allStatusesCache = $statuses;
        }

        return static::$allStatusesCache;
    }
}
