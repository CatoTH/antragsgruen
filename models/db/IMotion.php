<?php

namespace app\models\db;

use app\models\sectionTypes\ISectionType;
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
    const STATUS_DELETED               = -2;
    const STATUS_WITHDRAWN             = -1;
    const STATUS_UNCONFIRMED           = 0;
    const STATUS_DRAFT                 = 1;
    const STATUS_SUBMITTED_UNSCREENED  = 2;
    const STATUS_SUBMITTED_SCREENED    = 3;
    const STATUS_ACCEPTED              = 4;
    const STATUS_REJECTED              = 5;
    const STATUS_MODIFIED_ACCEPTED     = 6;
    const STATUS_MODIFIED              = 7;
    const STATUS_ADOPTED               = 8;
    const STATUS_COMPLETED             = 9;
    const STATUS_REFERRED              = 10;
    const STATUS_VOTE                  = 11;
    const STATUS_PAUSED                = 12;
    const STATUS_MISSING_INFORMATION   = 13;
    const STATUS_DISMISSED             = 14;
    const STATUS_COLLECTING_SUPPORTERS = 15;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_DELETED               => \Yii::t('structure', 'STATUS_DELETED'),
            static::STATUS_WITHDRAWN             => \Yii::t('structure', 'STATUS_WITHDRAWN'),
            static::STATUS_UNCONFIRMED           => \Yii::t('structure', 'STATUS_UNCONFIRMED'),
            static::STATUS_DRAFT                 => \Yii::t('structure', 'STATUS_DRAFT'),
            static::STATUS_SUBMITTED_UNSCREENED  => \Yii::t('structure', 'STATUS_SUBMITTED_UNSCREENED'),
            static::STATUS_SUBMITTED_SCREENED    => \Yii::t('structure', 'STATUS_SUBMITTED_SCREENED'),
            static::STATUS_ACCEPTED              => \Yii::t('structure', 'STATUS_ACCEPTED'),
            static::STATUS_REJECTED              => \Yii::t('structure', 'STATUS_REJECTED'),
            static::STATUS_MODIFIED_ACCEPTED     => \Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
            static::STATUS_MODIFIED              => \Yii::t('structure', 'STATUS_MODIFIED'),
            static::STATUS_ADOPTED               => \Yii::t('structure', 'STATUS_ADOPTED'),
            static::STATUS_COMPLETED             => \Yii::t('structure', 'STATUS_COMPLETED'),
            static::STATUS_REFERRED              => \Yii::t('structure', 'STATUS_REFERRED'),
            static::STATUS_VOTE                  => \Yii::t('structure', 'STATUS_VOTE'),
            static::STATUS_PAUSED                => \Yii::t('structure', 'STATUS_PAUSED'),
            static::STATUS_MISSING_INFORMATION   => \Yii::t('structure', 'STATUS_MISSING_INFORMATION'),
            static::STATUS_DISMISSED             => \Yii::t('structure', 'STATUS_DISMISSED'),
            static::STATUS_COLLECTING_SUPPORTERS => \Yii::t('structure', 'STATUS_COLLECTING_SUPPORTERS'),
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
     * @return ConsultationSettingsMotionSection
     */
    abstract public function getMySections();

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
        foreach ($this->sections as $section) {
            if (!$withoutTitle || $section != $title) {
                $sectionsIn[$section->sectionId] = $section;
            }
        }
        $sectionsOut = [];
        foreach ($this->getMySections() as $section) {
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
}
