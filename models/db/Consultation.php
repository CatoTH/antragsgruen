<?php

namespace app\models\db;

use app\components\MotionSorter;
use app\components\UrlHelper;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\exceptions\Internal;
use app\models\exceptions\NotFound;
use app\models\SearchResult;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $siteId
 * @property int $amendmentNumbering
 *
 * @property string $urlPath
 * @property string $title
 * @property string $titleShort
 * @property string $wordingBase
 * @property string $eventDateFrom
 * @property string $eventDateTo
 * @property string $adminEmail
 * @property string $dateCreation
 * @property string $dateDeletion
 * @property string $settings
 *
 * @property Site $site
 * @property Motion[] $motions
 * @property ConsultationText[] $texts
 * @property ConsultationOdtTemplate[] $odtTemplates
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationMotionType[] $motionTypes
 * @property ConsultationAgendaItem[] $agendaItems
 * @property ConsultationUserPrivilege[] $userPrivileges
 * @property ConsultationLog[] $logEntries
 * @property UserNotification[] $userNotifications
 */
class Consultation extends ActiveRecord
{

    /** @var null|Consultation */
    private static $current = null;

    /**
     * @param Consultation $consultation
     * @throws Internal
     */
    public static function setCurrent(Consultation $consultation)
    {
        if (static::$current) {
            throw new Internal('Current consultation already set');
        }
        static::$current = $consultation;
    }

    /**
     * @return Consultation|null
     */
    public static function getCurrent()
    {
        return static::$current;
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultation';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'dateCreation'], 'required'],
            [['title', 'titleShort', 'eventDateFrom', 'eventDateTo', 'urlPath'], 'safe'],
            [['adminEmail', 'wordingBase', 'amendmentNumbering'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['consultationId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @param string|null $motionSlug
     * @return Motion|null
     */
    public function getMotion($motionSlug)
    {
        if (is_null($motionSlug)) {
            return null;
        }
        foreach ($this->motions as $motion) {
            if (is_numeric($motionSlug) && $motion->id == $motionSlug && $motion->status != Motion::STATUS_DELETED) {
                return $motion;
            }
            if (!is_numeric($motionSlug) && $motion->slug == $motionSlug && $motion->status != Motion::STATUS_DELETED) {
                return $motion;
            }
        }
        return null;
    }

    /**
     * @return Motion|null
     */
    public function getForcedMotion()
    {
        if ($this->getSettings()->forceMotion === null) {
            return null;
        }
        return $this->getMotion($this->getSettings()->forceMotion);
    }

    /**
     * @param int $amendmentId
     * @return Amendment|null
     */
    public function getAmendment($amendmentId)
    {
        foreach ($this->motions as $motion) {
            if ($motion->status == Motion::STATUS_DELETED) {
                continue;
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->id == $amendmentId && $amendment->status != Amendment::STATUS_DELETED) {
                    return $amendment;
                }
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexts()
    {
        return $this->hasMany(ConsultationText::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOdtTemplates()
    {
        return $this->hasMany(ConsultationOdtTemplate::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserPrivileges()
    {
        return $this->hasMany(ConsultationUserPrivilege::class, ['consultationId' => 'id']);
    }

    /**
     * @param User $user
     * @return ConsultationUserPrivilege
     */
    public function getUserPrivilege(User $user)
    {
        foreach ($this->userPrivileges as $priv) {
            if ($priv->userId == $user->id) {
                return $priv;
            }
        }
        $priv                   = new ConsultationUserPrivilege();
        $priv->consultationId   = $this->id;
        $priv->userId           = $user->id;
        $priv->privilegeCreate  = 0;
        $priv->privilegeView    = 0;
        $priv->adminContentEdit = 0;
        $priv->adminScreen      = 0;
        $priv->adminSuper       = 0;
        return $priv;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogEntries()
    {
        return $this->hasMany(ConsultationLog::class, ['consultationId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionTypes()
    {
        return $this->hasMany(ConsultationMotionType::class, ['consultationId' => 'id'])
            ->andWhere(ConsultationMotionType::tableName() . '.status != ' . ConsultationMotionType::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserNotifications()
    {
        return $this->hasMany(UserNotification::class, ['consultationId' => 'id']);
    }

    /**
     * @param int $type
     * @return UserNotification[]
     */
    public function getUserNotificationsType($type)
    {
        $notis = [];
        foreach ($this->userNotifications as $userNotification) {
            if ($userNotification->notificationType == $type) {
                $notis[] = $userNotification;
            }
        }
        return $notis;
    }

    /**
     * @param int $motionTypeId
     * @return ConsultationMotionType
     * @throws NotFound
     */
    public function getMotionType($motionTypeId)
    {
        foreach ($this->motionTypes as $motionType) {
            if ($motionType->id == $motionTypeId) {
                return $motionType;
            }
        }
        throw new NotFound('Motion Type not found');
    }

    /** @var null|\app\models\settings\Consultation */
    private $settingsObject = null;

    /**
     * @return \app\models\settings\Consultation
     */
    public function getSettings()
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\Consultation($this->settings);
        }
        return $this->settingsObject;
    }

    /**
     * @param \app\models\settings\Consultation $settings
     */
    public function setSettings($settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = $settings->toJSON();
    }

    /**
     * @return IAmendmentNumbering
     */
    public function getAmendmentNumbering()
    {
        $numberings = IAmendmentNumbering::getNumberings();
        return new $numberings[$this->amendmentNumbering]();
    }

    /**
     * @param bool $includeWithdrawn
     * @return Motion[]
     */
    public function getVisibleMotions($includeWithdrawn = true)
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $this->getInvisibleMotionStati(!$includeWithdrawn))) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    /**
     * @param bool $includeWithdrawn
     * @return Motion[]
     */
    public function getVisibleMotionsSorted($includeWithdrawn = true)
    {
        $motions   = [];
        $motionIds = [];
        $items     = ConsultationAgendaItem::getSortedFromConsultation($this);
        foreach ($items as $agendaItem) {
            $newMotions = MotionSorter::getSortedMotionsFlat($this, $agendaItem->getVisibleMotions($includeWithdrawn));
            foreach ($newMotions as $newMotion) {
                $motions[]   = $newMotion;
                $motionIds[] = $newMotion->id;
            }
        }
        $noAgendaMotions = [];
        foreach ($this->getVisibleMotions($includeWithdrawn) as $motion) {
            if (!in_array($motion->id, $motionIds)) {
                $noAgendaMotions[] = $motion;
                $motionIds[]       = $motion->id;
            }
        }
        $noAgendaMotions = MotionSorter::getSortedMotionsFlat($this, $noAgendaMotions);
        $motions         = array_merge($motions, $noAgendaMotions);
        return $motions;
    }

    /**
     * @param int $privilege
     * @return bool
     *
     */
    public function havePrivilege($privilege)
    {
        $user = User::getCurrentUser();
        if (!$user) {
            return false;
        }
        return $user->hasPrivilege($this, $privilege);
    }


    /**
     * @return ConsultationSettingsTag[]
     */
    public function getSortedTags()
    {
        $tags = $this->tags;
        usort(
            $tags,
            function ($tag1, $tag2) {
                /** @var ConsultationSettingsTag $tag1 */
                /** @var ConsultationSettingsTag $tag2 */
                if ($tag1->position < $tag2->position) {
                    return -1;
                }
                if ($tag1->position > $tag2->position) {
                    return 1;
                }
                return 0;
            }
        );
        return $tags;
    }

    /**
     * @param bool $withdrawnInvisible
     * @return int[]
     */
    public function getInvisibleMotionStati($withdrawnInvisible = false)
    {
        $invisible = [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_UNCONFIRMED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_COLLECTING_SUPPORTERS,
            IMotion::STATUS_DRAFT_ADMIN,
            IMotion::STATUS_WITHDRAWN_INVISIBLE,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
        ];
        if (!$this->getSettings()->screeningMotionsShown) {
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED;
            $invisible[] = IMotion::STATUS_SUBMITTED_UNSCREENED_CHECKED;
        }
        if ($withdrawnInvisible) {
            $invisible[] = IMotion::STATUS_WITHDRAWN;
            $invisible[] = IMotion::STATUS_MODIFIED;
            $invisible[] = IMotion::STATUS_MODIFIED_ACCEPTED;
            $invisible[] = IMotion::STATUS_PROCESSED;
        }
        return $invisible;
    }

    /**
     * @return int[]
     */
    public function getUnreadableStati()
    {
        $invisible = [
            IMotion::STATUS_DELETED,
            IMotion::STATUS_UNCONFIRMED,
            IMotion::STATUS_DRAFT,
            IMotion::STATUS_MERGING_DRAFT_PRIVATE,
            IMotion::STATUS_MERGING_DRAFT_PUBLIC,
        ];
        return $invisible;
    }

    /**
     * @param bool $withdrawnInvisible
     * @return int[]
     */
    public function getInvisibleAmendmentStati($withdrawnInvisible = false)
    {
        return $this->getInvisibleMotionStati($withdrawnInvisible);
    }

    /**
     * @param int $motionTypeId
     * @return string
     */
    public function getNextMotionPrefix($motionTypeId)
    {
        $max_rev = 0;
        /** @var ConsultationMotionType $motionType */
        $motionType = null;
        foreach ($this->motionTypes as $t) {
            if ($t->id == $motionTypeId) {
                $motionType = $t;
            }
        }
        $prefix = $motionType->motionPrefix;
        if ($prefix == '') {
            $prefix = 'A';
        }
        foreach ($this->motions as $motion) {
            if ($motion->status != Motion::STATUS_DELETED) {
                if (mb_substr($motion->titlePrefix, 0, mb_strlen($prefix)) !== $prefix) {
                    continue;
                }
                $revs  = mb_substr($motion->titlePrefix, mb_strlen($prefix));
                $revnr = IntVal($revs);
                if ($revnr > $max_rev) {
                    $max_rev = $revnr;
                }
            }
        }
        return $prefix . ($max_rev + 1);
    }

    /**
     *
     */
    public function flushCacheWithChildren()
    {
        foreach ($this->motions as $motion) {
            $motion->flushCacheWithChildren();
        }
    }


    /**
     * @param string $text
     * @param array $backParams
     * @return \app\models\SearchResult[]
     * @throws \app\models\exceptions\Internal
     */
    public function fulltextSearch($text, $backParams)
    {
        $results = [];
        foreach ($this->motions as $motion) {
            if (in_array($motion->status, $this->getInvisibleMotionStati())) {
                continue;
            }
            $found = false;
            foreach ($motion->getActiveSections() as $section) {
                if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                    $found             = true;
                    $result            = new SearchResult();
                    $result->id        = 'motion' . $motion->id;
                    $result->typeTitle = $motion->motionType->titleSingular;
                    $result->type      = SearchResult::TYPE_MOTION;
                    $result->title     = $motion->getTitleWithPrefix();
                    $result->link      = UrlHelper::createMotionUrl($motion, 'view', $backParams);
                    $results[]         = $result;
                }
            }
            if (!$found) {
                foreach ($motion->amendments as $amend) {
                    if (in_array($amend->status, $this->getInvisibleAmendmentStati())) {
                        continue;
                    }
                    foreach ($amend->getActiveSections() as $section) {
                        if (!$found && $section->getSectionType()->matchesFulltextSearch($text)) {
                            $found             = true;
                            $result            = new SearchResult();
                            $result->id        = 'amendment' . $amend->id;
                            $result->typeTitle = \Yii::t('amend', 'amendment');
                            $result->type      = SearchResult::TYPE_AMENDMENT;
                            $result->title     = $amend->getTitle();
                            $result->link      = UrlHelper::createAmendmentUrl($amend, 'view', $backParams);
                            $results[]         = $result;
                        }
                    }
                }
            }
        }
        /*
         * @TODO: - Comments
         */
        return $results;
    }

    /**
     * @return bool
     */
    public function cacheOneMotionAffectsOthers()
    {
        if ($this->getSettings()->lineNumberingGlobal) {
            return true;
        }
        return false;
    }

    /**
     * @param string $prefix
     * @param null|Motion $ignore
     * @return null|Motion
     */
    public function findMotionWithPrefix($prefix, $ignore = null)
    {
        $prefixNorm = trim(mb_strtoupper($prefix));
        foreach ($this->motions as $mot) {
            $motPrefixNorm = trim(mb_strtoupper($mot->titlePrefix));
            if ($motPrefixNorm != '' && $motPrefixNorm === $prefixNorm && $mot->status != Motion::STATUS_DELETED) {
                if ($ignore === null || $ignore->id != $mot->id) {
                    return $mot;
                }
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAgendaWithMotions()
    {
        $ids    = [];
        $result = [];
        /**
         * @param $motion Motion
         */
        $addMotion = function ($motion) use (&$result) {
            $result[] = $motion;
            $result   = array_merge($result, MotionSorter::getSortedAmendments($this, $motion->getVisibleAmendments()));
        };

        $items = ConsultationAgendaItem::getSortedFromConsultation($this);
        foreach ($items as $agendaItem) {
            $result[] = $agendaItem;
            $motions  = MotionSorter::getSortedMotionsFlat($this, $agendaItem->getVisibleMotions());
            foreach ($motions as $motion) {
                $ids[] = $motion->id;
                $addMotion ($motion);
            }
        }
        $result[] = null;

        foreach ($this->getVisibleMotions() as $motion) {
            if (!(in_array($motion->id, $ids) || count($motion->replacedByMotions) > 0)) {
                $addMotion ($motion);
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function hasHelpPage()
    {
        $text = ConsultationText::findOne(['consultationId' => $this->id, 'textId' => 'help']);
        return ($text !== null);
    }

    /**
     */
    public function setDeleted()
    {
        $this->urlPath      = null;
        $this->dateDeletion = date('Y-m-d H:i:s');
        $this->save(false);
    }
}
