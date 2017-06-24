<?php

namespace app\models\db;

use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\ISupportType;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;

/**
 * Class IMotion
 * @package app\models\db
 *
 * @property string $titlePrefix
 * @property int $id
 * @property IMotionSection[] $sections
 * @property int $status
 */
abstract class IMotion extends ActiveRecord
{
    // The motion has been deleted and is not visible anymore. Only admins can delete a motion.
    const STATUS_DELETED = -2;

    // The motion has been withdrawn, either by the user or the admin.
    const STATUS_WITHDRAWN           = -1;
    const STATUS_WITHDRAWN_INVISIBLE = -3;

    // @TODO Is this status actually used?
    const STATUS_UNCONFIRMED = 0;

    // The user has written the motion, but not yet confirmed to submit it.
    const STATUS_DRAFT = 1;

    // The user has submitted the motion, but it's not yet visible. It's up to the admin to screen it now.
    const STATUS_SUBMITTED_UNSCREENED         = 2;
    const STATUS_SUBMITTED_UNSCREENED_CHECKED = 18;

    // The default state once the motion is visible
    const STATUS_SUBMITTED_SCREENED = 3;

    // This are stati motions and amendments get as their final state.
    // "Processed" is mostly used for amendments after merging amendments into th motion,
    // if it's unclear if it was adopted or rejected.
    const STATUS_ACCEPTED          = 4;
    const STATUS_REJECTED          = 5;
    const STATUS_MODIFIED_ACCEPTED = 6;
    const STATUS_PROCESSED         = 17;

    // The initiator is still collecting supporters to actually submit this motion.
    // It's visible only to those who know the link to it.
    const STATUS_COLLECTING_SUPPORTERS = 15;

    // Not yet visible, it's up to the admin to submit it
    const STATUS_DRAFT_ADMIN = 16;

    // Saved drafts while merging amendments into an motion
    const STATUS_MERGING_DRAFT_PUBLIC = 19;
    const STATUS_MERGING_DRAFT_PRIVATE = 20;

    // Purely informational statuses
    const STATUS_MODIFIED            = 7;
    const STATUS_ADOPTED             = 8;
    const STATUS_COMPLETED           = 9;
    const STATUS_REFERRED            = 10;
    const STATUS_VOTE                = 11;
    const STATUS_PAUSED              = 12;
    const STATUS_MISSING_INFORMATION = 13;
    const STATUS_DISMISSED           = 14;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_DELETED                      => \Yii::t('structure', 'STATUS_DELETED'),
            static::STATUS_WITHDRAWN                    => \Yii::t('structure', 'STATUS_WITHDRAWN'),
            static::STATUS_UNCONFIRMED                  => \Yii::t('structure', 'STATUS_UNCONFIRMED'),
            static::STATUS_DRAFT                        => \Yii::t('structure', 'STATUS_DRAFT'),
            static::STATUS_SUBMITTED_UNSCREENED         => \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED'),
            static::STATUS_SUBMITTED_UNSCREENED_CHECKED => \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED_CHECKED'),
            static::STATUS_SUBMITTED_SCREENED           => \Yii::t('structure', 'STATUS_SUBMITTED_SCREENED'),
            static::STATUS_ACCEPTED                     => \Yii::t('structure', 'STATUS_ACCEPTED'),
            static::STATUS_REJECTED                     => \Yii::t('structure', 'STATUS_REJECTED'),
            static::STATUS_MODIFIED_ACCEPTED            => \Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
            static::STATUS_MODIFIED                     => \Yii::t('structure', 'STATUS_MODIFIED'),
            static::STATUS_ADOPTED                      => \Yii::t('structure', 'STATUS_ADOPTED'),
            static::STATUS_COMPLETED                    => \Yii::t('structure', 'STATUS_COMPLETED'),
            static::STATUS_REFERRED                     => \Yii::t('structure', 'STATUS_REFERRED'),
            static::STATUS_VOTE                         => \Yii::t('structure', 'STATUS_VOTE'),
            static::STATUS_PAUSED                       => \Yii::t('structure', 'STATUS_PAUSED'),
            static::STATUS_MISSING_INFORMATION          => \Yii::t('structure', 'STATUS_MISSING_INFORMATION'),
            static::STATUS_DISMISSED                    => \Yii::t('structure', 'STATUS_DISMISSED'),
            static::STATUS_COLLECTING_SUPPORTERS        => \Yii::t('structure', 'STATUS_COLLECTING_SUPPORTERS'),
            static::STATUS_DRAFT_ADMIN                  => \Yii::t('structure', 'STATUS_DRAFT_ADMIN'),
            static::STATUS_PROCESSED                    => \Yii::t('structure', 'STATUS_PROCESSED'),
            static::STATUS_WITHDRAWN_INVISIBLE          => \Yii::t('structure', 'STATUS_WITHDRAWN_INVISIBLE'),
            static::STATUS_MERGING_DRAFT_PUBLIC         => \Yii::t('structure', 'STATUS_MERGING_DRAFT_PUBLIC'),
            static::STATUS_MERGING_DRAFT_PRIVATE        => \Yii::t('structure', 'STATUS_MERGING_DRAFT_PRIVATE'),
        ];
    }

    /**
     * @return int[]
     */
    public static function getScreeningStati()
    {
        return [
            static::STATUS_SUBMITTED_UNSCREENED,
            static::STATUS_SUBMITTED_UNSCREENED_CHECKED
        ];
    }

    /**
     * @return bool
     */
    public function isInScreeningProcess()
    {
        return in_array($this->status, IMotion::getScreeningStati());
    }

    /**
     * @return bool
     */
    public function isSubmitted()
    {
        return !in_array($this->status, [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_UNCONFIRMED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
        ]);
    }

    /**
     * @return int[]
     */
    public static function getStatiMarkAsDoneOnRewriting()
    {
        return [
            static::STATUS_PROCESSED,
            static::STATUS_ACCEPTED,
            static::STATUS_REJECTED,
            static::STATUS_MODIFIED_ACCEPTED,
        ];
    }

    /**
     * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface|ActiveQuery]] instance.
     * @throws InvalidConfigException if there is no primary key defined
     * @internal
     */
    protected static function findByCondition($condition)
    {
        $query = parent::findByCondition($condition);
        $query->andWhere('status != ' . static::STATUS_DELETED);
        return $query;
    }


    /**
     * @return bool
     */
    public function isVisible()
    {
        return !in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati());
    }

    /**
     * @return bool
     */
    public function isVisibleForAdmins()
    {
        return !in_array($this->status, [
            static::STATUS_DELETED,
            static::STATUS_MERGING_DRAFT_PUBLIC,
            static::STATUS_MERGING_DRAFT_PRIVATE,
        ]);
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return !in_array($this->status, $this->getMyConsultation()->getUnreadableStati());
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators();

    /**
     * @return string
     */
    public function getInitiatorsStr()
    {
        $inits = $this->getInitiators();
        $str   = [];
        foreach ($inits as $init) {
            $str[] = $init->getNameWithResolutionDate(false);
        }
        return implode(', ', $str);
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getSupporters();

    /**
     * @return ISupporter[]
     */
    abstract public function getLikes();

    /**
     * @return ISupporter[]
     */
    abstract public function getDislikes();

    /**
     * @return Consultation
     */
    abstract public function getMyConsultation();

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    abstract public function getTypeSections();

    /**
     * @return IMotionSection[]
     */
    abstract public function getActiveSections();

    /**
     * @return IMotionSection|null
     */
    public function getTitleSection()
    {
        foreach ($this->sections as $section) {
            if ($section->getSettings()->type == ISectionType::TYPE_TITLE) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @param bool $withoutTitle
     * @return IMotionSection[]
     */
    public function getSortedSections($withoutTitle = false)
    {
        $sectionsIn = [];
        $title      = $this->getTitleSection();
        foreach ($this->getActiveSections() as $section) {
            if (!$withoutTitle || $section != $title) {
                $sectionsIn[$section->sectionId] = $section;
            }
        }
        $sectionsOut = [];
        foreach ($this->getTypeSections() as $section) {
            if (isset($sectionsIn[$section->id])) {
                $sectionsOut[] = $sectionsIn[$section->id];
            }
        }
        return $sectionsOut;
    }

    /**
     * @return ConsultationMotionType
     */
    abstract public function getMyMotionType();

    /**
     * @return int
     */
    abstract public function getLikeDislikeSettings();

    /**
     * @return boolean
     */
    abstract public function isDeadlineOver();

    /**
     * @return bool
     */
    public function isSupportingPossibleAtThisStatus()
    {
        if (!($this->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_SUPPORT)) {
            return false;
        }
        if ($this->getMyMotionType()->supportType == ISupportType::COLLECTING_SUPPORTERS) {
            if ($this->status != IMotion::STATUS_COLLECTING_SUPPORTERS) {
                return false;
            }
        }
        if ($this->isDeadlineOver()) {
            return false;
        }
        return true;
    }
}
