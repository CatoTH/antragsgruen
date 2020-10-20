<?php

namespace app\models\db;

use app\components\{diff\AmendmentSectionFormatter, diff\DiffRenderer, HashedStaticCache, RSSExporter, Tools, UrlHelper};
use app\models\events\AmendmentEvent;
use app\models\exceptions\FormError;
use app\models\layoutHooks\Layout;
use app\models\notifications\{AmendmentProposedProcedure,
    AmendmentPublished as AmendmentPublishedNotification,
    AmendmentSubmitted as AmendmentSubmittedNotification,
    AmendmentWithdrawn as AmendmentWithdrawnNotification};
use app\models\policies\{All, IPolicy};
use app\models\sectionTypes\{Image, ISectionType, PDF, TextSimple};
use app\models\supportTypes\SupportBase;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changeEditorial
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 * @property int $globalAlternative
 * @property int|null $responsibilityId
 * @property string|null $responsibilityComment
 * @property string|null $extraData
 *
 * @property AmendmentComment[] $comments
 * @property AmendmentComment[] $privateComments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 * @property Amendment $proposalReferencedBy
 * @property VotingBlock $votingBlock
 * @property User $responsibilityUser
 */
class Amendment extends IMotion implements IRSSItem
{
    const EVENT_SUBMITTED       = 'submitted';
    const EVENT_PUBLISHED       = 'published';
    const EVENT_PUBLISHED_FIRST = 'published_first';

    public function init()
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'onPublish'], null, false);
        $this->on(static::EVENT_PUBLISHED_FIRST, [$this, 'onPublishFirst'], null, false);
        $this->on(static::EVENT_SUBMITTED, [$this, 'setInitialSubmitted'], null, false);
    }

    /**
     * @return string[]
     */
    public static function getProposedChangeStatuses()
    {
        $statuses = [
            IMotion::STATUS_ACCEPTED,
            IMotion::STATUS_REJECTED,
            IMotion::STATUS_MODIFIED_ACCEPTED,
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
        return $app->tablePrefix . 'amendment';
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $result = parent::save($runValidation, $attributeNames);

        if ($this->getMyMotion()) {
            $this->getMyMotion()->flushViewCache();
        }

        return $result;
    }

    /**
     * @return ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_DELETED)
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_PRIVATE);
    }

    /**
     * @return ActiveQuery
     */
    public function getPrivateComments()
    {
        $userId = User::getCurrentUser()->id;
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status = ' . AmendmentComment::STATUS_PRIVATE)
            ->andWhere(AmendmentComment::tableName() . '.userId = ' . IntVal($userId));
    }

    /**
     * @return AmendmentComment|null
     */
    public function getPrivateComment()
    {
        if (!User::getCurrentUser()) {
            return null;
        }
        // One-to-many-relashionship, but with the current version there can be only 0 or 1 comments
        foreach ($this->privateComments as $comment) {
            return $comment;
        }
        return null;
    }

    /**
     * @param int[] $types
     * @param string $sort
     * @param int|null $limit
     * @return MotionAdminComment[]
     */
    public function getAdminComments($types, $sort = 'desc', $limit = null)
    {
        return AmendmentAdminComment::find()
            ->where(['amendmentId' => $this->id, 'status' => $types])
            ->orderBy(['dateCreation' => $sort])
            ->limit($limit)->all();
    }

    /**
     * @return MotionAdminComment[]
     */
    public function getAllAdminComments()
    {
        return AmendmentAdminComment::find()->where(['amendmentId' => $this->id])->all();
    }

    /**
     * @return ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::class, ['amendmentId' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(AmendmentSection::class, ['amendmentId' => 'id']);
    }

    /**
     * @param null|int $filerType
     * @return AmendmentSection[]
     */
    public function getActiveSections($filerType = null)
    {
        $sections = [];
        foreach ($this->sections as $section) {
            if ($section->getSettings()) {
                if ($filerType === null || $section->getSettings()->type == $filerType) {
                    $sections[] = $section;
                }
            }
        }
        return $sections;
    }

    public function getMyProposalReference(): ?Amendment
    {
        if ($this->proposalReferenceId) {
            return $this->getMyConsultation()->getAmendment($this->proposalReferenceId);
        } else {
            return null;
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getProposalReferencedBy()
    {
        return $this->hasOne(Amendment::class, ['proposalReferenceId' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId']);
    }

    /**
     * @return ActiveQuery
     */
    public function getResponsibilityUser()
    {
        return $this->hasOne(User::class, ['id' => 'responsibilityId']);
    }

    /**
     * @param int $sectionId
     * @return AmendmentSection|null
     */
    public function getSection($sectionId)
    {
        foreach ($this->sections as $section) {
            if ($section->sectionId == $sectionId) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['id', 'motionId', 'status', 'textFixed', 'proposalStatus', 'proposalReferenceId'], 'number'],
        ];
    }


    public function getTitle(): string
    {
        $motion = $this->getMyMotion();
        if ($motion->titlePrefix !== '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $motion->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        $prefix = ($this->titlePrefix != '' ? $this->titlePrefix : \yii::t('amend', 'amendment'));
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $prefix . \yii::t('amend', 'amend_for') . $motion->title;
        } else {
            if ($this->getMyMotion()->titlePrefix != '') {
                if ($showMotionPrefix) {
                    $str = $prefix . \yii::t('amend', 'amend_for');
                    $str .= $motion->titlePrefix . ': ' . $motion->title;
                    return $str;
                } else {
                    return $prefix . ': ' . $motion->title;
                }
            } else {
                return $prefix . \yii::t('amend', 'amend_for') . $motion->title;
            }
        }
    }

    public function getTitleWithPrefix(): string
    {
        return $this->getTitle();
    }

    public function getShortTitle(bool $includeMotionPrefix = true): string
    {
        if ($this->getMyMotion()->titlePrefix !== '' && $includeMotionPrefix) {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $this->getMyMotion()->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
        } else {
            if ($this->getMyMotion()->titlePrefix !== '') {
                if ($showMotionPrefix) {
                    return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->titlePrefix;
                } else {
                    return $this->titlePrefix;
                }
            } else {
                return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
            }
        }
    }

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->isMyAmendment($this->id)) {
            return $current;
        } else {
            /** @var Motion $motion */
            $motion = Motion::findOne($this->motionId);
            if (!$motion) {
                return null;
            }
            return Consultation::findOne($motion->consultationId);
        }
    }

    private $myMotion = null;

    public function getMyMotion(): ?Motion
    {
        if (!$this->myMotion) {
            $current = Consultation::getCurrent();
            if ($current) {
                $motion = $current->getMotion($this->motionId);
                if ($motion) {
                    $this->myMotion = $motion;
                } else {
                    $this->myMotion = Motion::findOne($this->motionId);
                }
            } else {
                $this->myMotion = Motion::findOne($this->motionId);
            }
        }
        return $this->myMotion;
    }

    /**
     * @return ActiveQuery
     */
    public function getMotionJoin()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    public function getTypeSections()
    {
        return $this->getMyMotionType()->motionSections;
    }

    /**
     * @param string $changeId
     * @return array
     */
    public function getInlineChangeData($changeId)
    {
        if ($this->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
            return $this->proposalReferencedBy->getInlineChangeData($changeId);
        }
        $time = Tools::dateSql2timestamp($this->dateCreation) * 1000;
        return [
            'data-cid'              => $changeId,
            'data-userid'           => '',
            'data-username'         => $this->getInitiatorsStr(),
            'data-changedata'       => '',
            'data-time'             => $time,
            'data-last-change-time' => $time,
            'data-append-hint'      => '[' . $this->titlePrefix . ']',
            'data-link'             => UrlHelper::createAmendmentUrl($this),
            'data-amendment-id'     => $this->id,
        ];
    }

    /**
     * @param int $firstLine
     * @param int $lineLength
     * @param string[] $original
     * @param string[] $new
     * @return int
     * @throws \app\models\exceptions\Internal
     */
    public static function calcFirstDiffLineCached($firstLine, $lineLength, $original, $new)
    {
        $cacheFunc = 'calcFirstDiffLineCached';
        $cacheDeps = [$firstLine, $lineLength, $original, $new];

        $cache = HashedStaticCache::getCache($cacheFunc, $cacheDeps);
        if ($cache !== false) {
            return $cache;
        }

        $firstLineFallback = $firstLine;

        for ($i = 0; $i < count($original) && $i < count($new); $i++) {
            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($original[$i]);
            $formatter->setTextNew($new[$i]);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES, 0);

            if (count($diffGroups) > 0) {
                $firstLine = $diffGroups[0]['lineFrom'];
                HashedStaticCache::setCache($cacheFunc, $cacheDeps, $firstLine);
                return $firstLine;
            }
        }

        HashedStaticCache::setCache($cacheFunc, $cacheDeps, $firstLineFallback);
        return $firstLineFallback;
    }


    /**
     * @return int
     * @throws \app\models\exceptions\Internal
     */
    public function getFirstDiffLine()
    {
        $cached = $this->getCacheItem('lines.getFirstDiffLine');
        if ($cached !== null) {
            return $cached;
        }
        $firstLine  = $this->getMyMotion()->getFirstLineNumber();
        $lineLength = $this->getMyConsultation()->getSettings()->lineLength;
        $original   = $new = [];

        foreach ($this->getActiveSections() as $section) {
            if ($section->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $original[] = $section->getOriginalMotionSection()->getData();
            $new[]      = $section->data;
        }

        $firstLine = static::calcFirstDiffLineCached($firstLine, $lineLength, $original, $new);

        $this->setCacheItem('lines.getFirstDiffLine', $firstLine);
        return $firstLine;
    }

    public static function compareByLineNumbers(Amendment $ae1, Amendment $ae2): int
    {
        /*
         * Sort order:
         * - First line number
         * - Title prefix, if given (i.e., it is screened)
         * - If only one amendment has a title prefix, this one comes first
         * - if nothing else helps, then ordering by ID / submission time
         */
        $first1 = $ae1->getFirstDiffLine();
        $first2 = $ae2->getFirstDiffLine();

        if ($first1 < $first2) {
            return -1;
        }
        if ($first1 > $first2) {
            return 1;
        }

        if ($ae1->titlePrefix && $ae2->titlePrefix) {
            $tit1 = explode('-', $ae1->titlePrefix);
            $tit2 = explode('-', $ae2->titlePrefix);
            if (count($tit1) > 2 && count($tit1) === count($tit2)) {
                return $tit1[count($tit1) - 1] <=> $tit2[count($tit2) - 1];
            } else {
                return strcasecmp($ae1->titlePrefix, $ae2->titlePrefix);
            }
        } elseif ($ae1->titlePrefix) {
            return -1;
        } elseif ($ae2->titlePrefix) {
            return 1;
        } else {
            return $ae1->id <=> $ae2->id;
        }
    }


    /**
     * @param Consultation $consultation
     * @param Amendment[] $amendments
     * @return Amendment[]
     * @throws \app\models\exceptions\Internal
     */
    public static function sortByLineNumbers(Consultation $consultation, $amendments)
    {
        foreach ($amendments as $am) {
            $am->getFirstDiffLine(); // Initialize the cache
        }

        usort($amendments, [Amendment::class, 'compareByLineNumbers']);

        return $amendments;
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleMotionStatuses());
        $query             = Amendment::find();
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($invisibleStatuses, $consultation) {
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy("amendment.dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }


    /**
     * @param Consultation $consultation
     * @return Amendment[]
     */
    public static function getScreeningAmendments(Consultation $consultation)
    {
        $query = Amendment::find();
        $query->where('amendment.status IN (' . implode(', ', static::getScreeningStatuses()) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($consultation) {
                    $invisibleStatuses = array_map('IntVal', $consultation->getInvisibleMotionStatuses());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
                }
            ]
        );
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getInitiators()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        }
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getSupporters()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_SUPPORTER) {
                $return[] = $supp;
            }
        }
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getLikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        }
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getDislikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        }
        return $return;
    }

    public function iAmInitiator(): bool
    {
        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_INITIATOR && $supp->userId === $user->id) {
                return true;
            }
        }
        return false;
    }

    public function canSeeProposedProcedure(?string $procedureToken): bool
    {
        if ($procedureToken && AmendmentProposedProcedure::getPpOpenAcceptToken($this) === $procedureToken) {
            return true;
        }
        return $this->iAmInitiator();
    }

    public function canEdit(): bool
    {
        if ($this->status === static::STATUS_DRAFT) {
            $hadLoggedInUser = false;
            foreach ($this->amendmentSupporters as $supp) {
                $currUser = User::getCurrentUser();
                if ($supp->role === AmendmentSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    if ($currUser && $currUser->id == $supp->userId) {
                        return true;
                    }
                }
                if ($supp->role === MotionSupporter::ROLE_INITIATOR && $supp->userId === null) {
                    if ($currUser && $currUser->hasPrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
                        return true;
                    }
                }
            }
            if ($hadLoggedInUser) {
                return false;
            } else {
                if ($this->getMyMotionType()->getAmendmentPolicy()->getPolicyID() === All::getPolicyID()) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->getMyConsultation()->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            return $this->getMyMotionType()->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS);
        }

        return false;
    }

    public function canWithdraw(): bool
    {
        if (!in_array($this->status, [
            Amendment::STATUS_SUBMITTED_SCREENED,
            Amendment::STATUS_SUBMITTED_UNSCREENED,
            Amendment::STATUS_COLLECTING_SUPPORTERS
        ])
        ) {
            return false;
        }
        return $this->iAmInitiator();
    }

    public function isSupportingPossibleAtThisStatus(): bool
    {
        if (!($this->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_SUPPORT)) {
            return false;
        }

        $supportSettings = $this->getMyMotionType()->getAmendmentSupporterSettings();
        if ($supportSettings->type === SupportBase::COLLECTING_SUPPORTERS) {
            if ($supportSettings->allowMoreSupporters && $supportSettings->allowSupportingAfterPublication) {
                // If it's activated explicitly, then supporting is allowed in every status, also after the deadline
                return true;
            }

            if ($this->status !== IMotion::STATUS_COLLECTING_SUPPORTERS) {
                return false;
            }
        }
        if ($this->isDeadlineOver() && !$supportSettings->allowSupportingAfterPublication) {
            return false;
        }

        return true;
    }

    public function canFinishSupportCollection(): bool
    {
        $supportType = $this->getMyMotionType()->getAmendmentSupportTypeClass();
        return $this->getPermissionsObject()->canFinishSupportCollection($this, $supportType);
    }

    /**
     * @param bool $ignoreCollisionProblems
     * @return bool
     * @throws \app\models\exceptions\Internal
     */
    public function canMergeIntoMotion($ignoreCollisionProblems = false)
    {
        if ($this->getMyConsultation()->havePrivilege(User::PRIVILEGE_CONTENT_EDIT)) {
            return true;
        } elseif ($this->getMyMotion()->iAmInitiator()) {
            $policy = $this->getMyMotionType()->initiatorsCanMergeAmendments;
            if ($policy == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION) {
                return true;
            } elseif ($policy == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION && $ignoreCollisionProblems) {
                return true;
            } elseif ($policy == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION && !$ignoreCollisionProblems) {
                return (count($this->getCollidingAmendments()) == 0);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /** @var null|MotionSectionParagraphAmendment[] */
    private $changedParagraphCache = null;

    /**
     * @param MotionSection[] $motionSections
     * @param bool $lineNumbers
     * @return MotionSectionParagraphAmendment[]
     * @throws \app\models\exceptions\Internal
     */
    public function getChangedParagraphs($motionSections, $lineNumbers)
    {
        if ($lineNumbers && $this->changedParagraphCache !== null) {
            return $this->changedParagraphCache;
        }
        $paragraphs = [];
        foreach ($motionSections as $section) {
            if ($section->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $paras = $section->getTextParagraphObjects($lineNumbers, true, true);
            foreach ($paras as $para) {
                foreach ($para->amendmentSections as $amSec) {
                    if ($amSec->amendmentId === $this->id) {
                        $paragraphs[] = $amSec;
                    }
                }
            }
        }
        if ($lineNumbers) {
            $this->changedParagraphCache = $paragraphs;
        }
        return $paragraphs;
    }

    /**
     * @return Amendment[]
     * @throws \app\models\exceptions\Internal
     */
    public function getCollidingAmendments()
    {
        $mySections = [];
        foreach ($this->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $mySections[$section->sectionId] = $section->data;
        }

        $colliding = [];
        foreach ($this->getMyMotion()->getAmendmentsRelevantForCollisionDetection([$this]) as $amend) {
            foreach ($amend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($mySections[$section->sectionId], false, false);
                if (count($coll) > 0) {
                    $colliding[$amend->id] = $amend;
                }
            }
        }

        return $colliding;
    }

    public function withdraw(): void
    {
        if ($this->status === Amendment::STATUS_DRAFT) {
            $this->status = static::STATUS_DELETED;
        } elseif (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses())) {
            $this->status = static::STATUS_WITHDRAWN_INVISIBLE;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->flushCache(true);

        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_WITHDRAW, $this->id);
        new AmendmentWithdrawnNotification($this);
    }

    public function needsCollectionPhase(): bool
    {
        $supportType = $this->getMyMotionType()->getAmendmentSupportTypeClass();
        return $this->iNeedsCollectionPhase($supportType);
    }

    public function getSubmitButtonLabel(): string
    {
        if ($this->needsCollectionPhase()) {
            return \Yii::t('amend', 'button_submit_create');
        } elseif ($this->getMyConsultation()->getSettings()->screeningAmendments) {
            return \Yii::t('amend', 'button_submit_submit');
        } else {
            return \Yii::t('amend', 'button_submit_publish');
        }
    }

    public function setInitialSubmitted(): void
    {
        if ($this->needsCollectionPhase()) {
            $this->status = Amendment::STATUS_COLLECTING_SUPPORTERS;
        } elseif ($this->getMyConsultation()->getSettings()->screeningAmendments) {
            $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        } else {
            $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
            if ($this->titlePrefix == '') {
                $numbering         = $this->getMyConsultation()->getAmendmentNumbering();
                $this->titlePrefix = $numbering->getAmendmentNumber($this, $this->getMyMotion());
            }
        }
        $this->save();

        new AmendmentSubmittedNotification($this);
    }

    public function setScreened(): void
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix === '') {
            $numbering         = $this->getMyConsultation()->getAmendmentNumbering();
            $this->titlePrefix = $numbering->getAmendmentNumber($this, $this->getMyMotion());
        }
        $this->save(true);
        $this->trigger(Amendment::EVENT_PUBLISHED, new AmendmentEvent($this));
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_SCREEN, $this->id);
    }

    public function setUnscreened(): void
    {
        $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_UNSCREEN, $this->id);
    }

    public function setProposalPublished(): void
    {
        if ($this->proposalVisibleFrom) {
            return;
        }
        $this->proposalVisibleFrom = date('Y-m-d H:i:s');
        $this->save();

        $consultation = $this->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_PUBLISH_PROPOSAL, $this->id);
    }

    public function setDeleted(): void
    {
        $this->status = Amendment::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_DELETE, $this->id);
    }

    public function isDeleted(): bool
    {
        if ($this->status === Amendment::STATUS_DELETED) {
            return true;
        }
        if (!$this->getMyMotion() || $this->getMyMotion()->status === Motion::STATUS_DELETED) {
            return true;
        }
        if (!$this->getMyConsultation()) {
            return true;
        }
        return false;
    }

    public function onPublish(): void
    {
        $this->flushCacheWithChildren(null);
        $this->setTextFixedIfNecessary();

        $init   = $this->getInitiators();
        $initId = (count($init) > 0 ? $init[0]->userId : null);
        ConsultationLog::log($this->getMyConsultation(), $initId, ConsultationLog::AMENDMENT_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            $this->trigger(static::EVENT_PUBLISHED_FIRST, new AmendmentEvent($this));
        }
    }

    public function onPublishFirst(): void
    {
        new AmendmentPublishedNotification($this);
    }

    public function setTextFixedIfNecessary(bool $save = true): void
    {
        if ($this->getMyConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleAmendmentStatuses())) {
            return;
        }
        $this->textFixed = 1;
        if ($save) {
            $this->save(true);
        }
    }

    public function flushCacheWithChildren(?array $items): void
    {
        if ($items) {
            $this->flushCacheItems($items);
        } else {
            $this->flushCache();
        }
        \Yii::$app->cache->delete($this->getPdfCacheKey());
    }

    public function getPdfCacheKey(): string
    {
        return 'amendment-pdf-' . $this->id;
    }

    public function getFilenameBase(bool $noUmlaut): string
    {
        $motionTitle  = $this->getMyMotion()->title;
        $motionPrefix = $this->getMyMotion()->titlePrefix;
        if ($motionPrefix !== '' && mb_strpos($this->titlePrefix, $motionPrefix) === false) {
            $title = $motionPrefix . '_' . $this->titlePrefix . ' ' . $motionTitle;
        } else {
            $title = $this->titlePrefix . ' ' . $motionTitle;
        }
        $filename = Tools::sanitizeFilename($title, $noUmlaut);
        $filename = (mb_strlen($filename) > 59 ? mb_substr($filename, 0, 59) : $filename);
        return $filename;
    }

    public function addToFeed(RSSExporter $feed): void
    {
        // @TODO Inline styling
        $content = '';

        $firstLine  = $this->getMyMotion()->getFirstLineNumber();
        $lineLength = $this->getMyConsultation()->getSettings()->lineLength;

        foreach ($this->getActiveSections() as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);

            if (count($diffGroups) > 0) {
                $content .= '<h2>' . Html::encode($section->getSettings()->title) . '</h2>';
                $content .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
                $content .= TextSimple::formatDiffGroup($diffGroups);
                $content .= '</div>';
                $content .= '</section>';
            }
        }

        if ($this->changeExplanation) {
            $content .= '<h2>' . \Yii::t('amend', 'reason') . '</h2>';
            $content .= '<div class="paragraph"><div class="text">';
            $content .= $this->changeExplanation;
            $content .= '</div></div>';
        }

        $feed->addEntry(
            UrlHelper::createAmendmentUrl($this),
            $this->getTitle(),
            $this->getInitiatorsStr(),
            $content,
            Tools::dateSql2timestamp($this->dateCreation)
        );
    }

    public function getDataTable(): array
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) === 1) {
            $first         = $inits[0];
            $keyResolution = \Yii::t('export', 'ResolutionDate');
            $keySingle     = \Yii::t('export', 'InitiatorSingle');
            if ($first->personType === MotionSupporter::PERSON_ORGANIZATION && $first->resolutionDate > 0) {
                $return[$keySingle]     = $first->organization;
                $return[$keyResolution] = Tools::formatMysqlDate($first->resolutionDate, null, false);
            } else {
                $return[$keySingle] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('export', 'InitiatorMulti')] = implode("\n", $initiators);
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses(false))) {
            $return[\Yii::t('motion', 'status')] = IMotion::getStatusNames()[$this->status];
        }

        return $return;
    }

    public function getMyMotionType(): ConsultationMotionType
    {
        return $this->getMyMotion()->getMyMotionType();
    }

    /**
     * @param ConsultationMotionType $motionType
     * @throws FormError
     */
    public function setMotionType(ConsultationMotionType $motionType)
    {
        if (!$this->getMyMotionType()->isCompatibleTo($motionType)) {
            throw new FormError('This amendment cannot be changed to the type ' . $motionType->titleSingular);
        }

        $typeMapping = $this->getMyMotionType()->getSectionCompatibilityMapping($motionType);
        $mySections  = $this->getSortedSections(false);
        for ($i = 0; $i < count($mySections); $i++) {
            if (!isset($typeMapping[$mySections[$i]->sectionId])) {
                continue;
            }
            $mySections[$i]->sectionId = $typeMapping[$mySections[$i]->sectionId];
            if (!$mySections[$i]->save()) {
                $err = print_r($mySections[$i]->getErrors(), true);
                throw new FormError('Something terrible happened while changing the motion type: ' . $err);
            }
        }
    }

    public function getLikeDislikeSettings(): int
    {
        return $this->getMyMotionType()->amendmentLikesDislikes;
    }

    public function isDeadlineOver(): bool
    {
        return !$this->getMyMotionType()->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS);
    }

    public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool
    {
        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) &&
            $this->proposalReferenceId && $this->getMyConsultation()->getAmendment($this->proposalReferenceId)) {
            return true;
        }

        // This amendment is obsoleted by an amendment with a modification proposal
        if ($includeOtherAmendments && $this->proposalStatus === Amendment::STATUS_OBSOLETED_BY) {
            $obsoletedBy = $this->getMyConsultation()->getAmendment($this->proposalComment);
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->hasAlternativeProposaltext($includeOtherAmendments, $internalNestingLevel + 1);
            }
        }

        return false;
    }

    public function hasVisibleAlternativeProposaltext(): bool
    {
        return ($this->hasAlternativeProposaltext(true) && (
            $this->isProposalPublic() ||
            User::havePrivilege($this->getMyConsultation(), User::PRIVILEGE_CHANGE_PROPOSALS) ||
            ($this->proposalFeedbackHasBeenRequested() && $this->iAmInitiator())
        ));
    }

    /*
     * Returns the modification proposed and the amendment to which the modification was directly proposed
     * (which has not to be this very amendment, in case this amendment is obsoleted by another amendment)
     */
    public function getAlternativeProposaltextReference(int $internalNestingLevel = 0): ?array
    {
        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) &&
            $this->getMyProposalReference()) {
            return [
                'amendment'    => $this,
                'modification' => $this->getMyProposalReference(),
            ];
        }

        // This amendment is obsoleted by an amendment with a modification proposal
        if ($this->proposalStatus === Amendment::STATUS_OBSOLETED_BY) {
            $obsoletedBy = $this->getMyConsultation()->getAmendment($this->proposalComment);
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->getAlternativeProposaltextReference($internalNestingLevel + 1);
            }
        }

        return null;
    }

    /*
     * If no proposed procedure is set, the checkbox in merge_amendments_init should always be preselected,
     * except for global alternatives.
     * If there is one, it depends if either the amendment, the proposed procedure or the vote was set as accepted,
     * or is set as "modified accepted".
     */
    public function markForMergingByDefault(bool $hasProposals): bool
    {
        if ($this->globalAlternative) {
            return false;
        }
        if (!$hasProposals) {
            return true;
        }
        if ($this->status === static::STATUS_ACCEPTED || $this->proposalStatus === static::STATUS_ACCEPTED) {
            return true;
        }
        if ($this->status === static::STATUS_PROPOSED_MODIFIED_AMENDMENT ||
            $this->proposalStatus === static::STATUS_MODIFIED_ACCEPTED) {
            return true;
        }
        if ($this->status === static::STATUS_VOTE || $this->proposalStatus === static::STATUS_VOTE) {
            if ($this->votingStatus === static::STATUS_ACCEPTED) {
                return true;
            }
        }
        return false;
    }

    public function getFormattedStatus(): string
    {
        $statusNames = Amendment::getStatusNames();
        $status      = '';
        switch ($this->status) {
            case Amendment::STATUS_SUBMITTED_UNSCREENED:
            case Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED:
                $status = '<span class="unscreened">' . Html::encode($statusNames[$this->status]) . '</span>';
                break;
            case Amendment::STATUS_SUBMITTED_SCREENED:
                $status = '<span class="screened">' . \Yii::t('amend', 'screened_hint') . '</span>';
                break;
            case Amendment::STATUS_COLLECTING_SUPPORTERS:
                $status = Html::encode($statusNames[$this->status]);
                $status .= ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
                $status .= IPolicy::getPolicyNames()[$this->getMyMotionType()->policySupportAmendments] . ')</small>';
                break;
            default:
                $status .= Html::encode($statusNames[$this->status]);
        }
        if (trim($this->statusString) !== '') {
            $status .= ' <small>(' . Html::encode($this->statusString) . ')</small>';
        }

        return Layout::getFormattedAmendmentStatus($status, $this);
    }

    /**
     * @param boolean $includeVoted
     * @return Amendment[]
     * @throws \app\models\exceptions\Internal
     */
    public function collidesWithOtherProposedAmendments($includeVoted)
    {
        $collidesWith = [];

        if ($this->getMyProposalReference()) {
            $sections = $this->getMyProposalReference()->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        } else {
            $sections = $this->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        }
        $newSections = [];
        foreach ($sections as $section) {
            $newSections[$section->sectionId] = $section->data;
        }

        foreach ($this->getMyMotion()->getAmendmentsProposedToBeIncluded($includeVoted, [$this->id]) as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollisions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $collidesWith, true)) {
                        $collidesWith[] = $amendment;
                    }
                }
            }
        }

        return $collidesWith;
    }

    public function getLink(bool $absolute = false): string
    {
        $url = UrlHelper::createAmendmentUrl($this);
        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }
        return $url;
    }

    public function getUserdataExportObject(): array
    {
        $data = [
            'title'            => $this->getTitle(),
            'title_prefix'     => $this->titlePrefix,
            'motion_url'       => $this->getMyMotion()->getLink(true),
            'url'              => $this->getLink(true),
            'initiators'       => [],
            'changed_sections' => [],
            'reason'           => $this->changeExplanation,
            'editorial_change' => $this->changeEditorial,
            'date_creation'    => $this->dateCreation,
            'date_publication' => $this->datePublication,
            'date_resolution'  => $this->dateResolution,
            'status'           => $this->status,
            'status_string'    => $this->statusString,
            'status_formatted' => $this->getFormattedStatus(),
        ];

        foreach ($this->amendmentSupporters as $amendmentSupporter) {
            if ($amendmentSupporter->role !== MotionSupporter::ROLE_INITIATOR) {
                continue;
            }
            if ($amendmentSupporter->personType === MotionSupporter::PERSON_ORGANIZATION) {
                $type = 'organization';
            } else {
                $type = 'person';
            }
            $data['initiators'][] = [
                'type'            => $type,
                'name'            => $amendmentSupporter->name,
                'organization'    => $amendmentSupporter->organization,
                'resolution_date' => $amendmentSupporter->resolutionDate,
                'contact_name'    => $amendmentSupporter->contactName,
                'contact_phone'   => $amendmentSupporter->contactPhone,
                'contact_email'   => $amendmentSupporter->contactEmail,
            ];
        }

        foreach ($this->getSortedSections(false) as $section) {
            $type = $section->getSettings()->type;
            if ($type === ISectionType::TYPE_IMAGE) {
                /** @var Image $type */
                $type                       = $section->getSectionType();
                $data['changed_sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'download_url'  => $type->getImageUrl(true),
                    'metadata'      => $section->metadata,
                ];
            } elseif ($type === ISectionType::TYPE_PDF_ATTACHMENT || $type === ISectionType::TYPE_PDF_ALTERNATIVE) {
                /** @var PDF $type */
                $type                       = $section->getSectionType();
                $data['changed_sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'download_url'  => $type->getPdfUrl(true),
                    'metadata'      => $section->metadata,
                ];
            } else {
                $data['changed_sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'data'          => $section->getData(),
                    'metadata'      => $section->metadata,
                ];
            }
        }

        return $data;
    }
}
