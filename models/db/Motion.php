<?php

namespace app\models\db;

use app\components\MotionSorter;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\components\EmailNotifications;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\exceptions\NotAmendable;
use app\models\exceptions\NotFound;
use app\models\layoutHooks\Layout;
use app\models\mergeAmendments\Draft;
use app\models\notifications\MotionSubmitted as MotionSubmittedNotification;
use app\models\notifications\MotionWithdrawn as MotionWithdrawnNotification;
use app\models\notifications\MotionEdited as MotionEditedNotification;
use app\models\policies\IPolicy;
use app\models\events\MotionEvent;
use app\models\sectionTypes\Image;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\PDF;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $motionTypeId
 * @property int $parentMotionId
 * @property int $agendaItemId
 * @property string $title
 * @property string $titlePrefix
 * @property int $status
 * @property string $statusString
 * @property int $nonAmendable
 * @property string $noteInternal
 * @property string $cache
 * @property int $textFixed
 * @property string $slug
 *
 * @property ConsultationMotionType $motionType
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property MotionComment[] $comments
 * @property MotionComment[] $privateComments
 * @property ConsultationSettingsTag[] $tags
 * @property MotionSection[] $sections
 * @property MotionSupporter[] $motionSupporters
 * @property ConsultationAgendaItem $agendaItem
 * @property Motion $replacedMotion
 * @property Motion[] $replacedByMotions
 * @property VotingBlock $votingBlock
 */
class Motion extends IMotion implements IRSSItem
{
    use CacheTrait;

    const EVENT_SUBMITTED = 'submitted';
    const EVENT_PUBLISHED = 'published';
    const EVENT_PUBLISHED_FIRST = 'published_first';
    const EVENT_MERGED = 'merged'; // Called on the newly created motion

    /**
     */
    public function init()
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'onPublish'], null, false);
        $this->on(static::EVENT_PUBLISHED_FIRST, [$this, 'onPublishFirst'], null, false);
        $this->on(static::EVENT_SUBMITTED, [$this, 'setInitialSubmitted'], null, false);
        $this->on(static::EVENT_MERGED, [$this, 'onMerged'], null, false);
    }

    /**
     * @return string[]
     */
    public static function getProposedChangeStatuses()
    {
        $statuses = [
            IMotion::STATUS_ACCEPTED,
            IMotion::STATUS_REJECTED,
            IMotion::STATUS_REFERRED,
            IMotion::STATUS_VOTE,
            IMotion::STATUS_OBSOLETED_BY,
            IMotion::STATUS_CUSTOM_STRING,
        ];
        if (Consultation::getCurrent()) {
            $statuses = Consultation::getCurrent()->site->getBehaviorClass()->getProposedChangeStatuses($statuses);
        }

        return $statuses;
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;

        return $app->tablePrefix . 'motion';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(MotionComment::class, ['motionId' => 'id'])
                    ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED)
                    ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_PRIVATE);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrivateComments()
    {
        $userId = User::getCurrentUser()->id;

        return $this->hasMany(MotionComment::class, ['motionId' => 'id'])
                    ->andWhere(MotionComment::tableName() . '.status = ' . MotionComment::STATUS_PRIVATE)
                    ->andWhere(MotionComment::tableName() . '.userId = ' . IntVal($userId));
    }

    /**
     * @param int|null $sectionId
     * @param int $paragraphNo
     *
     * @return MotionComment|null
     */
    public function getPrivateComment($sectionId, $paragraphNo)
    {
        if (!User::getCurrentUser()) {
            return null;
        }
        foreach ($this->privateComments as $comment) {
            if ($comment->sectionId === $sectionId && $comment->paragraph === $paragraphNo) {
                return $comment;
            }
        }

        return null;
    }

    /**
     * @param int[] $types
     * @param string $sort
     * @param int|null $limit
     *
     * @return MotionAdminComment[]
     */
    public function getAdminComments($types, $sort = 'desc', $limit = null)
    {
        return MotionAdminComment::find()
                                 ->where(['motionId' => $this->id, 'status' => $types])
                                 ->orderBy(['dateCreation' => $sort])
                                 ->limit($limit)->all();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSupporters()
    {
        return $this->hasMany(MotionSupporter::class, ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasMany(Amendment::class, ['motionId' => 'id'])
                    ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['id' => 'tagId'])
                    ->viaTable('motionTag', ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(MotionSection::class, ['motionId' => 'id']);
    }

    /**
     * @param null|int $filer_type
     *
     * @return MotionSection[]
     */
    public function getActiveSections($filer_type = null)
    {
        $sections = [];
        foreach ($this->sections as $section) {
            if ($section->getSettings()) {
                if ($filer_type === null || $section->getSettings()->type == $filer_type) {
                    $sections[] = $section;
                }
            }
        }

        return $sections;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionType()
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItem()
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacedMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'parentMotionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacedByMotions()
    {
        return $this->hasMany(Motion::class, ['parentMotionId' => 'id']);
    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getMotion($this->id)) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    public function getTypeSections()
    {
        return $this->motionType->motionSections;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'motionTypeId'], 'required'],
            [['id', 'consultationId', 'motionTypeId', 'status', 'textFixed', 'agendaItemId', 'nonAmendable'], 'number'],
            [['title'], 'safe'],
        ];
    }

    /**
     */
    public function refreshTitle()
    {
        $this->refresh();
        $section = $this->getTitleSection();
        if ($section) {
            $this->title = $section->data;
        } else {
            $this->title = '';
        }
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     *
     * @return Motion[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleMotionStatuses());

        $query = Motion::find();
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }

    /**
     * @param Consultation $consultation
     *
     * @return Motion[]
     */
    public static function getScreeningMotions(Consultation $consultation)
    {
        $query = Motion::find();
        $query->where('motion.status IN (' . implode(', ', static::getScreeningStatuses()) . ')');
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }

    /**
     * @return string ("Application: John <Doe>")
     */
    public function getTitleWithIntro()
    {
        try {
            $intro = $this->getMyMotionType()->getSettingsObj()->motionTitleIntro;
        } catch (\Exception $e) {
            $intro = '';
        }
        if (mb_strlen($intro) > 0 && mb_substr($intro, mb_strlen($intro) - 1, 1) !== ' ') {
            $intro .= ' ';
        }

        return $intro . $this->title;
    }

    /**
     * @return string ("A1: Application: John <Doe>")
     */
    public function getTitleWithPrefix()
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->getTitleWithIntro();
        }

        $name = $this->titlePrefix;
        if (mb_strlen($name) > 1 && !in_array(mb_substr($name, mb_strlen($name) - 1, 1), [':', '.'])) {
            $name .= ':';
        }
        $name .= ' ' . $this->getTitleWithIntro();

        return $name;
    }

    /**
     * @return string ("A1: Application: John &lt;Doe&gt;")
     */
    public function getEncodedTitleWithPrefix()
    {
        $title = $this->getTitleWithPrefix();
        $title = Html::encode($title);
        $title = str_replace(" - \n", "<br>", $title);
        $title = str_replace("\n", "<br>", $title);

        return $title;
    }

    /**
     * @return string ("A1new")
     */
    public function getNewTitlePrefix()
    {
        return static::getNewTitlePrefixInternal($this->titlePrefix);
    }

    /**
     * @param bool $includeWithdrawn
     *
     * @return Amendment[]
     */
    public function getVisibleAmendments($includeWithdrawn = true)
    {
        $filtered   = $this->getMyConsultation()->getInvisibleAmendmentStatuses($includeWithdrawn);
        $amendments = [];
        foreach ($this->amendments as $amend) {
            if (!in_array($amend->status, $filtered)) {
                $amendments[] = $amend;
            }
        }

        return $amendments;
    }

    /**
     * @param null|Amendment[] $exclude
     *
     * @return Amendment[]
     */
    public function getAmendmentsRelevantForCollisionDetection($exclude = null)
    {
        $amendments = [];
        foreach ($this->amendments as $amendment) {
            if ($exclude && in_array($amendment, $exclude, true)) {
                continue;
            }
            if ($amendment->isVisibleForAdmins() && $amendment->status !== Amendment::STATUS_DRAFT) {
                $amendments[] = $amendment;
            }
        }

        return $amendments;
    }

    /**
     * @param boolean $includeVoted
     * @param null|int[] $exclude
     *
     * @return Amendment[]
     */
    public function getAmendmentsProposedToBeIncluded($includeVoted, $exclude = null)
    {
        $amendments = [];
        foreach ($this->amendments as $amendment) {
            if ($exclude && in_array($amendment->id, $exclude)) {
                continue;
            }
            if (!$amendment->isVisibleForProposalAdmins()) {
                continue;
            }
            $toBeCheckedStatuses = [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_ACCEPTED];
            if ($includeVoted) {
                $toBeCheckedStatuses[] = Amendment::STATUS_VOTE;
            }
            if (in_array($amendment->proposalStatus, $toBeCheckedStatuses)) {
                $amendments[] = $amendment;
            }
        }

        return $amendments;
    }

    /**
     * @param bool $includeWithdrawn
     *
     * @return Amendment[]
     * @throws Internal
     */
    public function getVisibleAmendmentsSorted($includeWithdrawn = true)
    {
        $amendments = $this->getVisibleAmendments($includeWithdrawn);

        return MotionSorter::getSortedAmendments($this->getMyConsultation(), $amendments);
    }

    /**
     * @return bool
     */
    public function iAmInitiator()
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    public function canEdit()
    {
        return $this->getPermissionsObject()->motionCanEdit($this);
    }

    /**
     * @return bool
     */
    public function canWithdraw()
    {
        return $this->getPermissionsObject()->motionCanWithdraw($this);
    }

    /**
     * @return bool
     */
    public function canMergeAmendments()
    {
        return $this->getPermissionsObject()->motionCanMergeAmendments($this);
    }

    /**
     * @return bool
     */
    public function canCreateResolution()
    {
        return User::havePrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT);
    }

    /**
     * @return bool
     */
    public function canFinishSupportCollection()
    {
        return $this->getPermissionsObject()->motionCanFinishSupportCollection($this);
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @param bool $throwExceptions
     *
     * @return bool
     * @throws NotAmendable
     * @throws Internal
     */
    public function isCurrentlyAmendable($allowAdmins = true, $assumeLoggedIn = false, $throwExceptions = false)
    {
        $permissions = $this->getPermissionsObject();

        return $permissions->isCurrentlyAmendable($this, $allowAdmins, $assumeLoggedIn, $throwExceptions);
    }

    /**
     * @return Motion[]
     */
    public function getVisibleReplacedByMotions()
    {
        $replacedByMotions = [];
        foreach ($this->replacedByMotions as $replMotion) {
            if (!in_array($replMotion->status, $this->getMyConsultation()->getInvisibleMotionStatuses())) {
                $replacedByMotions[] = $replMotion;
            }
        }

        return $replacedByMotions;
    }

    /**
     * @param boolean $onlyPublic
     *
     * @return Draft|null
     */
    public function getMergingDraft($onlyPublic)
    {
        if ($onlyPublic) {
            $status = [Motion::STATUS_MERGING_DRAFT_PUBLIC];
        } else {
            $status = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];
        }
        $motion = Motion::findOne([
            'parentMotionId' => $this->id,
            'status'         => $status,
        ]);
        if ($motion) {
            $public = ($motion->status === Motion::STATUS_MERGING_DRAFT_PUBLIC);

            return Draft::initFromJson($this, $public, $motion->getDateTime(), $motion->sections[0]->dataRaw);
        } else {
            return null;
        }
    }

    /**
     * @return Motion|null
     */
    public function getMergingUnconfirmed()
    {
        return Motion::findOne([
            'parentMotionId' => $this->id,
            'status'         => Motion::STATUS_DRAFT,
        ]);
    }

    /**
     * @return bool
     */
    public function isResolution()
    {
        return in_array($this->status, [static::STATUS_RESOLUTION_FINAL, static::STATUS_RESOLUTION_PRELIMINARY]);
    }

    /**
     * @return bool
     */
    public function isSocialSharable()
    {
        if ($this->getMyConsultation()->getSettings()->forceLogin) {
            return false;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses(false))) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getIconCSSClass()
    {
        foreach ($this->tags as $tag) {
            return $tag->getCSSIconClass();
        }

        return 'glyphicon glyphicon-file';
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        $cached = $this->getCacheItem('getNumberOfCountableLines');
        if ($cached !== null) {
            return $cached;
        }

        $num = 0;
        foreach ($this->getSortedSections() as $section) {
            /** @var MotionSection $section */
            $num += $section->getNumberOfCountableLines();
        }

        $this->setCacheItem('getNumberOfCountableLines', $num);

        return $num;
    }

    /**
     * @return int
     */
    public function getFirstLineNumber()
    {
        $cached = $this->getCacheItem('getFirstLineNumber');
        if ($cached !== null) {
            return $cached;
        }

        if ($this->getMyConsultation()->getSettings()->lineNumberingGlobal) {
            $motions      = $this->getMyConsultation()->getVisibleMotions(false);
            $motionBlocks = MotionSorter::getSortedMotions($this->getMyConsultation(), $motions);
            $lineNo       = 1;
            foreach ($motionBlocks as $motions) {
                foreach ($motions as $motion) {
                    /** @var Motion $motion */
                    if ($motion->id === $this->id) {
                        $this->setCacheItem('getFirstLineNumber', $lineNo);

                        return $lineNo;
                    } else {
                        $lineNo += $motion->getNumberOfCountableLines();
                    }
                }
            }

            // This is a invisible motion. The final line numbers are therefore not determined yet
            return 1;
        } else {
            $this->setCacheItem('getFirstLineNumber', 1);

            return 1;
        }
    }

    /**
     * @return MotionSupporter[]
     */
    public function getInitiators()
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        };

        return $return;
    }

    /**
     * @return MotionSupporter[]
     */
    public function getSupporters()
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_SUPPORTER) {
                $return[] = $supp;
            }
        };
        usort($return, function (MotionSupporter $supp1, MotionSupporter $supp2) {
            if ($supp1->position > $supp2->position) {
                return 1;
            }
            if ($supp1->position < $supp2->position) {
                return -1;
            }
            if ($supp1->id > $supp2->id) {
                return 1;
            }
            if ($supp1->id < $supp2->id) {
                return -1;
            }

            return 0;
        });

        return $return;
    }

    /**
     * @return MotionSupporter[]
     */
    public function getLikes()
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        };

        return $return;
    }

    /**
     * @return MotionSupporter[]
     */
    public function getDislikes()
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        };

        return $return;
    }

    /**
     */
    public function withdraw()
    {
        if ($this->status === Motion::STATUS_DRAFT) {
            $this->status = static::STATUS_DELETED;
        } elseif (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses())) {
            $this->status = static::STATUS_WITHDRAWN_INVISIBLE;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->flushCacheStart();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_WITHDRAW, $this->id);
        new MotionWithdrawnNotification($this);
    }

    /**
     * @return bool
     */
    public function needsCollectionPhase()
    {
        $needsCollectionPhase = false;
        if ($this->motionType->getMotionSupportTypeClass()->collectSupportersBeforePublication()) {
            $isOrganization = false;
            foreach ($this->getInitiators() as $initiator) {
                if ($initiator->personType === ISupporter::PERSON_ORGANIZATION) {
                    $isOrganization = true;
                }
            }
            $supporters    = count($this->getSupporters());
            $minSupporters = $this->motionType->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
            if ($supporters < $minSupporters && !$isOrganization) {
                $needsCollectionPhase = true;
            }
        }

        return $needsCollectionPhase;
    }

    /**
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        if ($this->needsCollectionPhase()) {
            return \Yii::t('motion', 'button_submit_create');
        } elseif ($this->getMyConsultation()->getSettings()->screeningMotions) {
            return \Yii::t('motion', 'button_submit_submit');
        } else {
            return \Yii::t('motion', 'button_submit_publish');
        }
    }

    /**
     */
    public function setInitialSubmitted()
    {
        if ($this->needsCollectionPhase()) {
            $this->status = Motion::STATUS_COLLECTING_SUPPORTERS;
        } elseif ($this->getMyConsultation()->getSettings()->screeningMotions) {
            $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        } else {
            $this->status = Motion::STATUS_SUBMITTED_SCREENED;
            if ($this->statusString == '') {
                $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId);
            }
        }
        $this->dateCreation = date('Y-m-d H:i:s');
        $this->save();

        new MotionSubmittedNotification($this);
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix === '') {
            $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId);
        }
        $this->save(true);
        $this->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($this));
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_UNSCREEN, $this->id);
    }

    /**
     */
    public function setProposalPublished()
    {
        if ($this->proposalVisibleFrom) {
            return;
        }
        $this->proposalVisibleFrom = date('Y-m-d H:i:s');
        $this->save();

        $consultation = $this->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_PUBLISH_PROPOSAL, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Motion::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_DELETE, $this->id);
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        if ($this->status === Motion::STATUS_DELETED) {
            return true;
        }
        if (!$this->getMyConsultation()) {
            return true;
        }

        return false;
    }

    /**
     */
    public function onMerged()
    {
        if ($this->datePublication === null && $this->status === Motion::STATUS_SUBMITTED_SCREENED) {
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            new MotionEditedNotification($this);
        }
    }

    /**
     *
     */
    public function onPublish()
    {
        $this->flushCacheWithChildren();
        $this->setTextFixedIfNecessary();

        $init   = $this->getInitiators();
        $initId = (count($init) > 0 ? $init[0]->userId : null);
        ConsultationLog::log($this->getMyConsultation(), $initId, ConsultationLog::MOTION_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();
            $this->trigger(static::EVENT_PUBLISHED_FIRST, new MotionEvent($this));
        }
    }

    /**
     * @throws Internal
     */
    public function onPublishFirst()
    {
        UserNotification::notifyNewMotion($this);
        EmailNotifications::sendMotionOnPublish($this);
    }

    /**
     * @param bool $save
     */
    public function setTextFixedIfNecessary($save = true)
    {
        if ($this->getMyConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses())) {
            return;
        }
        $this->textFixed = 1;
        if ($save) {
            $this->save(true);
        }
    }

    /**
     */
    public function flushCacheStart()
    {
        if ($this->getMyConsultation()->cacheOneMotionAffectsOthers()) {
            $this->getMyConsultation()->flushCacheWithChildren();
        } else {
            $this->flushCacheWithChildren();
        }
    }

    /**
     */
    public function flushCacheWithChildren()
    {
        $this->flushCache();
        \Yii::$app->cache->delete($this->getPdfCacheKey());
        foreach ($this->sections as $section) {
            $section->flushCache();
        }
        foreach ($this->amendments as $amend) {
            $amend->flushCacheWithChildren();
        }
    }

    /**
     * @return string
     */
    public function getPdfCacheKey()
    {
        return 'motion-pdf-' . $this->id;
    }

    /**
     * @param bool
     *
     * @return string
     */
    public function getFilenameBase($noUmlaut)
    {
        $motionTitle = (mb_strlen($this->title) > 100 ? mb_substr($this->title, 0, 100) : $this->title);
        $title       = $this->titlePrefix . ' ' . $motionTitle;

        return Tools::sanitizeFilename($title, $noUmlaut);
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function createSlug()
    {
        $motionTitle = (mb_strlen($this->title) > 70 ? mb_substr($this->title, 0, 70) : $this->title);
        $title       = Tools::sanitizeFilename($motionTitle, true);

        $random = \Yii::$app->getSecurity()->generateRandomKey(2);
        $random = ord($random[0]) + ord($random[1]) * 256;

        return $title . '-' . $random;
    }

    /**
     * @return string
     */
    public function getMotionSlug()
    {
        if ($this->slug != '') {
            return $this->slug;
        } else {
            return $this->id;
        }
    }

    /**
     * @return string
     */
    public function getBreadcrumbTitle()
    {
        if ($this->titlePrefix && !$this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix;
        } else {
            return $this->motionType->titleSingular;
        }
    }

    /**
     * @param RSSExporter $feed
     *
     * @throws Internal
     */
    public function addToFeed(RSSExporter $feed)
    {
        // @TODO Inline styling
        $content = '';
        foreach ($this->getSortedSections(true) as $section) {
            $content .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
            $content .= $section->getSectionType()->getSimple(false);
        }
        $feed->addEntry(
            UrlHelper::createMotionUrl($this),
            $this->getTitleWithPrefix(),
            $this->getInitiatorsStr(),
            $content,
            Tools::dateSql2timestamp($this->dateCreation)
        );
    }

    /**
     * @return array
     * @throws Internal
     */
    public function getDataTable()
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) === 1) {
            $first          = $inits[0];
            $resolutionDate = $first->resolutionDate;
            if ($first->personType === MotionSupporter::PERSON_ORGANIZATION && $resolutionDate > 0) {
                $return[\Yii::t('export', 'InitiatorOrga')]  = $first->organization;
                $return[\Yii::t('export', 'ResolutionDate')] = Tools::formatMysqlDate($resolutionDate, null, false);

                // For applications, the title usually is the name of the person -> no need to repeat the name
            } elseif (!$first->name || mb_stripos($this->title, $first->name) === false) {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('export', 'InitiatorMulti')] = implode("\n", $initiators);
        }
        if ($this->agendaItem) {
            $return[\Yii::t('export', 'AgendaItem')] = $this->agendaItem->getShownCode(true) .
                                                       ' ' . $this->agendaItem->title;
        }
        if (count($this->tags) > 1) {
            $tags = [];
            foreach ($this->tags as $tag) {
                $tags[] = $tag->title;
            }
            $return[\Yii::t('export', 'TopicMulti')] = implode("\n", $tags);
        } elseif (count($this->tags) === 1) {
            $return[\Yii::t('export', 'TopicSingle')] = $this->tags[0]->title;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses(false))) {
            $return[\Yii::t('motion', 'status')] = IMotion::getStatusNames()[$this->status];
        }

        return $return;
    }

    /**
     * @param string $prefix
     * @param null|Amendment $ignore
     *
     * @return null|Amendment
     */
    public function findAmendmentWithPrefix($prefix, $ignore = null)
    {
        $numbering = $this->getMyConsultation()->getAmendmentNumbering();

        return $numbering->findAmendmentWithPrefix($this, $prefix, $ignore);
    }

    /**
     * @return ConsultationMotionType
     */
    public function getMyMotionType()
    {
        try {
            return $this->getMyConsultation()->getMotionType($this->motionTypeId);
        } catch (NotFound $e) {
            return $this->motionType;
        }
    }

    /**
     * @param ConsultationMotionType $motionType
     *
     * @throws FormError
     */
    public function setMotionType(ConsultationMotionType $motionType)
    {
        if (!$this->motionType->isCompatibleTo($motionType)) {
            throw new FormError('This motion cannot be changed to the type ' . $motionType->titleSingular);
        }
        if (count($this->getSortedSections(false)) !== count($this->motionType->motionSections)) {
            throw new FormError('This motion cannot be changed as it seems to be inconsistent');
        }

        foreach ($this->amendments as $amendment) {
            $amendment->setMotionType($motionType);
        }

        $mySections = $this->getSortedSections(false);
        for ($i = 0; $i < count($mySections); $i++) {
            $mySections[$i]->sectionId = $motionType->motionSections[$i]->id;
            if (!$mySections[$i]->save()) {
                $err = print_r($mySections[$i]->getErrors(), true);
                throw new FormError('Something terrible happened while changing the motion type: ' . $err);
            }
        }

        $this->motionTypeId = $motionType->id;
        $this->save();
        $this->refresh();
    }

    /**
     * @return string
     */
    public function getFormattedStatus()
    {
        $status = '';

        $screeningMotionsShown = $this->getMyConsultation()->getSettings()->screeningMotionsShown;
        $statusNames           = Motion::getStatusNames();
        if ($this->isInScreeningProcess()) {
            $status .= '<span class="unscreened">' . Html::encode($statusNames[$this->status]) . '</span>';
        } elseif ($this->status == Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
            $status .= '<span class="screened">' . \Yii::t('motion', 'screened_hint') . '</span>';
        } elseif ($this->status == Motion::STATUS_COLLECTING_SUPPORTERS) {
            $status .= Html::encode($statusNames[$this->status]);
            $status .= ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
            $status .= IPolicy::getPolicyNames()[$this->motionType->policySupportMotions] . ')</small>';
        } else {
            $status .= Html::encode($statusNames[$this->status]);
        }
        if (trim($this->statusString) != '') {
            $status .= ' <small>(' . Html::encode($this->statusString) . ')</string>';
        }

        return Layout::getFormattedMotionStatus($status, $this);
    }

    /**
     * @return int
     */
    public function getLikeDislikeSettings()
    {
        return $this->motionType->motionLikesDislikes;
    }

    /**
     * @return boolean
     */
    public function isDeadlineOver()
    {
        return !$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getLink($absolute = false)
    {
        $url = UrlHelper::createMotionUrl($this);
        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    /**
     * @return array
     */
    public function getUserdataExportObject()
    {
        $data = [
            'title'            => $this->title,
            'title_prefix'     => $this->titlePrefix,
            'url'              => $this->getLink(true),
            'initiators'       => [],
            'sections'         => [],
            'date_creation'    => $this->dateCreation,
            'date_publication' => $this->datePublication,
            'date_resolution'  => $this->dateResolution,
            'status'           => $this->status,
            'status_string'    => $this->statusString,
            'status_formatted' => $this->getFormattedStatus(),
        ];

        foreach ($this->motionSupporters as $motionSupporter) {
            if ($motionSupporter->role !== MotionSupporter::ROLE_INITIATOR) {
                continue;
            }
            if ($motionSupporter->personType === MotionSupporter::PERSON_ORGANIZATION) {
                $type = 'organization';
            } else {
                $type = 'person';
            }
            $data['initiators'][] = [
                'type'            => $type,
                'name'            => $motionSupporter->name,
                'organization'    => $motionSupporter->organization,
                'resolution_date' => $motionSupporter->resolutionDate,
                'contact_name'    => $motionSupporter->contactName,
                'contact_phone'   => $motionSupporter->contactPhone,
                'contact_email'   => $motionSupporter->contactEmail,
            ];
        }

        foreach ($this->getSortedSections(false) as $section) {
            if ($section->getSettings()->type === ISectionType::TYPE_IMAGE) {
                /** @var Image $type */
                $type               = $section->getSectionType();
                $data['sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'download_url'  => $type->getImageUrl(true),
                    'metadata'      => $section->metadata,
                ];
            } elseif ($section->getSettings()->type === ISectionType::TYPE_PDF) {
                /** @var PDF $type */
                $type               = $section->getSectionType();
                $data['sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'download_url'  => $type->getPdfUrl(true),
                    'metadata'      => $section->metadata,
                ];
            } else {
                $data['sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'data'          => $section->data,
                    'metadata'      => $section->metadata,
                ];
            }
        }

        return $data;
    }
}
