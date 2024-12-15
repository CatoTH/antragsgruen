<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\proposedProcedure\Agenda;
use app\models\settings\{AntragsgruenApp, Privileges, MotionSection as MotionSectionSettings};
use app\components\{diff\AmendmentSectionFormatter,
    diff\DiffRenderer,
    HashedStaticCache,
    RequestContext,
    RSSExporter,
    Tools,
    UrlHelper};
use app\models\events\AmendmentEvent;
use app\models\exceptions\FormError;
use app\models\layoutHooks\Layout;
use app\models\notifications\{AmendmentProposedProcedure,
    AmendmentPublished as AmendmentPublishedNotification,
    AmendmentSubmitted as AmendmentSubmittedNotification,
    AmendmentWithdrawn as AmendmentWithdrawnNotification};
use app\models\sectionTypes\{Image, ISectionType, PDF, TextSimple};
use app\models\supportTypes\SupportBase;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * @property int|null $id
 * @property int $motionId
 * @property int|null $amendingAmendmentId
 * @property string|null $titlePrefix
 * @property string $changeEditorial
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property int $status
 * @property string $statusString
 * @property int $notCommentable
 * @property string|null $noteInternal
 * @property int $textFixed
 * @property int $globalAlternative
 * @property int|null $responsibilityId
 * @property string|null $responsibilityComment
 * @property string|null $extraData
 *
 * @property Amendment|null $amendedAmendment
 * @property Amendment[] $amendingAmendments
 * @property AmendmentComment[] $comments
 * @property AmendmentComment[] $privateComments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 * @property Amendment|null $proposalReferencedByAmendment
 * @property Motion|null $proposalReferencedByMotion
 * @property VotingBlock|null $votingBlock
 * @property User|null $responsibilityUser
 * @property Vote[] $votes
 */
class Amendment extends IMotion implements IRSSItem
{
    public const EVENT_SUBMITTED       = 'submitted';
    public const EVENT_PUBLISHED       = 'published';
    public const EVENT_PUBLISHED_FIRST = 'published_first';

    private const PROPERTIES_RELEVANT_FOR_MOTION_VIEW_CACHE = ['status', 'titlePrefix'];

    public const EXTRA_DATA_VIEW_MODE_FULL = 'view_mode_full'; // Boolean value

    public function init(): void
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'onPublish'], null, false);
        $this->on(static::EVENT_PUBLISHED_FIRST, [$this, 'onPublishFirst'], null, false);
        $this->on(static::EVENT_SUBMITTED, [$this, 'setInitialSubmitted'], null, false);
    }

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendment';
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $viewCacheNeedsRebuild = count(array_intersect(array_keys($this->getDirtyAttributes()), self::PROPERTIES_RELEVANT_FOR_MOTION_VIEW_CACHE)) > 0;

        $result = parent::save($runValidation, $attributeNames);

        if ($this->getMyMotion() && $viewCacheNeedsRebuild) {
            $this->getMyMotion()->flushViewCache();
        }

        return $result;
    }

    public function getAgendaItem(): ActiveQuery
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    /**
     * This returns the amendment being amended by this amendment
     */
    public function getAmendedAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendingAmendmentId'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    /**
     * This returns the amendments amending this amendment
     */
    public function getAmendingAmendments(): ActiveQuery
    {
        return $this->hasMany(Amendment::class, ['amendingAmendmentId' => 'id'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    public function getComments(): ActiveQuery
    {
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_DELETED)
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_PRIVATE);
    }

    public function getPrivateComments(): ActiveQuery
    {
        $userId = User::getCurrentUser()->id;
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status = ' . AmendmentComment::STATUS_PRIVATE)
            ->andWhere(AmendmentComment::tableName() . '.userId = ' . IntVal($userId));
    }

    public function getPrivateComment(): ?AmendmentComment
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
     * @return AmendmentAdminComment[]
     */
    public function getAdminComments(array $types, string $sort = 'desc', ?int $limit = null): array
    {
        /** @var AmendmentAdminComment[] $comments */
        $comments = AmendmentAdminComment::find()
            ->where(['amendmentId' => $this->id, 'status' => $types])
            ->orderBy(['dateCreation' => $sort])
            ->limit($limit)->all();
        return $comments;
    }

    /**
     * @return AmendmentAdminComment[]
     */
    public function getAllAdminComments(): array
    {
        /** @var AmendmentAdminComment[] $comments */
        $comments = AmendmentAdminComment::find()->where(['amendmentId' => $this->id])->all();
        return $comments;
    }

    public function getAmendmentSupporters(): ActiveQuery
    {
        return $this->hasMany(AmendmentSupporter::class, ['amendmentId' => 'id']);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['id' => 'tagId'])
                    ->viaTable('amendmentTag', ['amendmentId' => 'id']);
    }

    public function getSections(): ActiveQuery
    {
        return $this->hasMany(AmendmentSection::class, ['amendmentId' => 'id']);
    }

    /**
     * @return AmendmentSection[]
     */
    public function getActiveSections(?int $filterType = null, bool $showAdminSections = false): array
    {
        $sections = [];
        $hadNonPublicSections = false;
        foreach ($this->sections as $section) {
            if (!$section->getSettings()) {
                // Internal problem - maybe an accidentally deleted motion type
                continue;
            }
            if ($filterType !== null && $section->getSettings()->type !== $filterType) {
                continue;
            }
            if ($section->getSettings()->getSettingsObj()->public !== MotionSectionSettings::PUBLIC_YES && !$showAdminSections) {
                $hadNonPublicSections = true;
                continue;
            }

            $sections[] = $section;
        }

        if ($showAdminSections && $hadNonPublicSections && !$this->iAmInitiator() &&
            !User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            // @TODO Find a solution to edit motions before submitting when not logged in
            throw new Internal('Can only set showAdminSections for admins');
        }

        return $sections;
    }

    public function getProposalReferencedByAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['proposalReferenceId' => 'id']);
    }

    public function getProposalReferencedByMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['proposalReferenceId' => 'id']);
    }

    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    public function getResponsibilityUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'responsibilityId']);
    }

    public function getVotes(): ActiveQuery
    {
        return $this->hasMany(Vote::class, ['amendmentId' => 'id']);
    }

    public function getSection(int $sectionId): ?AmendmentSection
    {
        foreach ($this->sections as $section) {
            if ($section->sectionId == $sectionId) {
                return $section;
            }
        }
        return null;
    }

    public function rules(): array
    {
        return [
            [['motionId'], 'required'],
            [['id', 'motionId', 'status', 'textFixed', 'proposalStatus', 'proposalReferenceId', 'agendaItemId'], 'number'],
        ];
    }


    public function getTitle(): string
    {
        $motion = $this->getMyMotion();
        if ($motion->titlePrefix !== '') {
            $showMotionPrefix = (mb_stripos($this->getFormattedTitlePrefix() ?: '', $motion->getFormattedTitlePrefix()) === false);
        } else {
            $showMotionPrefix = false;
        }
        $prefix = $this->getFormattedTitlePrefix() ?: \Yii::t('amend', 'amendment');
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $prefix . \Yii::t('amend', 'amend_for') . $motion->title;
        } else {
            if ($this->getMyMotion()->getFormattedTitlePrefix()) {
                if ($showMotionPrefix) {
                    $str = $prefix . \Yii::t('amend', 'amend_for');
                    $str .= $motion->getFormattedTitlePrefix() . ': ' . $motion->title;
                    return $str;
                } else {
                    return $prefix . ': ' . $motion->title;
                }
            } else {
                return $prefix . \Yii::t('amend', 'amend_for') . $motion->title;
            }
        }
    }

    public function showTitlePrefix(): bool
    {
        // For statute amendments, the hideTitlePrefix is relevant; for regular amendments not.
        if ($this->getMyMotionType()->amendmentsOnly && $this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return false;
        }

        return trim($this->getFormattedTitlePrefix()) !== '';
    }

    public function getTitleWithPrefix(): string
    {
        return $this->getTitle();
    }

    public function getShortTitle(bool $includeMotionPrefix = true): string
    {
        if ($this->getMyMotion()->titlePrefix !== '' && $includeMotionPrefix) {
            $showMotionPrefix = (mb_stripos($this->getFormattedTitlePrefix(), $this->getMyMotion()->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->getFormattedTitlePrefix() . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
        } else {
            if ($this->getMyMotion()->getFormattedTitlePrefix() !== '') {
                if ($showMotionPrefix) {
                    return $this->getFormattedTitlePrefix() . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->getFormattedTitlePrefix();
                } else {
                    return $this->getFormattedTitlePrefix();
                }
            } else {
                return $this->getFormattedTitlePrefix() . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
            }
        }
    }

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->isMyAmendment($this->id)) {
            return $current;
        } else {
            $motion = Motion::findOne($this->motionId);
            if (!$motion) {
                return null;
            }
            return Consultation::findOne($motion->consultationId);
        }
    }

    public function getMyAgendaItem(): ?ConsultationAgendaItem
    {
        if ($this->agendaItemId && $this->agendaItem) {
            return $this->agendaItem;
        } else {
            return $this->getMyMotion()->getMyAgendaItem();
        }
    }

    public function getMyTags(): array
    {
        return $this->getMyMotion()->tags;
    }

    private ?Motion $myMotion = null;

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

    public function getMotionJoin(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    public function getTypeSections(): array
    {
        return $this->getMyMotionType()->motionSections;
    }

    /**
     * @return Amendment[]
     */
    public function getVisibleAmendingAmendments(bool $includeWithdrawn = true): array
    {
        $filtered   = $this->getMyConsultation()->getStatuses()->getInvisibleAmendmentStatuses($includeWithdrawn);
        $amendments = [];
        foreach ($this->amendingAmendments as $amend) {
            if (!in_array($amend->status, $filtered)) {
                $amendments[] = $amend;
            }
        }

        return $amendments;
    }

    /**
     * @param array<array{original: string, new: string, firstLine: int}> $sectionData
     * @return array{from: int, to: int}
     * @throws Internal
     */
    public static function calcAffectedDiffLinesCached(array $sectionData, int $lineLength): array
    {
        $cache = HashedStaticCache::getInstance('calcAffectedDiffLinesCached', [$sectionData, $lineLength]);
        return $cache->getCached(function () use ($sectionData, $lineLength) {
            $firstAffectedLine = null;
            $lastAffectedLine = null;

            foreach ($sectionData as $section) {
                $formatter = new AmendmentSectionFormatter();
                $formatter->setTextOriginal($section['original']);
                $formatter->setTextNew($section['new']);
                $formatter->setFirstLineNo($section['firstLine']);
                $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES, 0);

                foreach ($diffGroups as $diffGroup) {
                    if ($firstAffectedLine === null) {
                        $firstAffectedLine = $diffGroup->lineFrom;
                    }
                    $lastAffectedLine = $diffGroup->lineTo;
                }
            }

            return [
                'from' => $firstAffectedLine ?? ($sectionData[0]['firstLine'] ?? 0),
                'to' => $lastAffectedLine ?? ($sectionData[0]['firstLine'] ?? 0),
            ];
        });
    }

    /**
     * @return array{from: int, to: int}
     */
    public function getAffectedLines(): array
    {
        $lineLength = $this->getMyConsultation()->getSettings()->lineLength;
        $sectionData = [];

        foreach ($this->getActiveSections() as $section) {
            if ($section->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $sectionData[] = [
                'original' => $section->getOriginalMotionSection()->getData(),
                'new' => $section->data,
                'firstLine' => $section->getFirstLineNumber(),
            ];
        }

        return self::calcAffectedDiffLinesCached($sectionData, $lineLength);
    }

    /**
     * @throws Internal
     */
    public function getFirstDiffLine(): int
    {
        $cached = $this->getCacheItem('lines.getFirstDiffLine');
        if ($cached !== null) {
            return $cached;
        }

        $affectedLines = $this->getAffectedLines();

        $this->setCacheItem('lines.getFirstDiffLine', $affectedLines['from']);
        return $affectedLines['from'];
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
     * @param Amendment[] $amendments
     * @return Amendment[]
     * @throws Internal
     */
    public static function sortByLineNumbers(Consultation $consultation, array $amendments): array
    {
        foreach ($amendments as $am) {
            $am->getFirstDiffLine(); // Initialize the cache
        }

        usort($amendments, [Amendment::class, 'compareByLineNumbers']);

        return $amendments;
    }

    /**
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, int $limit = 5): array
    {
        $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());
        $query             = Amendment::find();
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($invisibleStatuses, $consultation) {
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('motion.consultationId = ' . intval($consultation->id));
                }
            ]
        );
        $query->orderBy("amendment.dateCreation DESC");
        $query->offset(0)->limit($limit);
        /** @var Amendment[] $amendments */
        $amendments = $query->all();

        return $amendments;
    }


    /**
     * @return Amendment[]
     */
    public static function getScreeningAmendments(Consultation $consultation): array
    {
        $query = Amendment::find();
        $statuses = array_map('intval', $consultation->getStatuses()->getScreeningStatuses());
        $query->where('amendment.status IN (' . implode(', ', $statuses) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($consultation) {
                    $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
                    $query->andWhere('motion.consultationId = ' . intval($consultation->id));
                }
            ]
        );
        $query->orderBy("dateCreation DESC");
        /** @var Amendment[] $amendments */
        $amendments = $query->all();

        return $amendments;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getInitiators(): array
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
    public function getSupporters(bool $includeNonPublic = false): array
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role === AmendmentSupporter::ROLE_SUPPORTER) {
                if ($includeNonPublic || !$supp->isNonPublic()) {
                    $return[] = $supp;
                }
            }
        }
        usort($return, function (AmendmentSupporter $supp1, AmendmentSupporter $supp2) {
            if ($supp1->position > $supp2->position) {
                return 1;
            }
            if ($supp1->position < $supp2->position) {
                return -1;
            }
            return $supp1->id <=> $supp2->id;
        });

        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getLikes(): array
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
    public function getDislikes(): array
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
        $user = RequestContext::getYiiUser();
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

    public function canEditText(): bool
    {
        return $this->getPermissionsObject()->amendmentCanEditText($this);
    }

    public function canEditInitiators(): bool
    {
        return $this->getPermissionsObject()->amendmentCanEditInitiators($this);
    }

    public function canWithdraw(): bool
    {
        return $this->getPermissionsObject()->iMotionCanWithdraw($this);
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
            if ($this->hasEnoughSupporters($this->getMyMotionType()->getAmendmentSupportTypeClass()) && !$supportSettings->allowMoreSupporters) {
                return false;
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
     * @throws Internal
     */
    public function canMergeIntoMotion(bool $ignoreCollisionProblems = false): bool
    {
        if ($this->getMyMotionType()->amendmentsOnly) {
            return false;
        }
        if ($this->getMyConsultation()->havePrivilege(Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
            return true;
        } elseif ($this->getMyMotion()->iAmInitiator()) {
            return match ($this->getMyMotionType()->initiatorsCanMergeAmendments) {
                ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION => true,
                ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION => $ignoreCollisionProblems || count($this->getCollidingAmendments()) === 0,
                default => false,
            };
        } else {
            return false;
        }
    }

    /** @var null|MotionSectionParagraphAmendment[] */
    private ?array $changedParagraphCache = null;

    /**
     * @param MotionSection[] $motionSections
     * @return MotionSectionParagraphAmendment[]
     * @throws Internal
     */
    public function getChangedParagraphs(array $motionSections, bool $lineNumbers): array
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
     * @throws Internal
     */
    public function getCollidingAmendments(): array
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
        } elseif (in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses())) {
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

    public static function getNewNumberForAmendment(Amendment $amendment): string
    {
        if ($amendment->getMyMotionType()->amendmentsOnly) {
            return $amendment->getMyConsultation()->getNextMotionPrefix($amendment->getMyMotionType()->id, $amendment->getPublicTopicTags());
        } else {
            $numbering = $amendment->getMyConsultation()->getAmendmentNumbering();
            return $numbering->getAmendmentNumber($amendment, $amendment->getMyMotion());
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
            if ($this->titlePrefix === '') {
                $this->titlePrefix = Amendment::getNewNumberForAmendment($this);
            }
        }
        $this->save();

        new AmendmentSubmittedNotification($this);
    }

    public function setScreened(): void
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix === '') {
            $this->titlePrefix = Amendment::getNewNumberForAmendment($this);
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
        if (in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleAmendmentStatuses())) {
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
        if ($motionPrefix !== '' && !str_contains($this->getFormattedTitlePrefix(), $motionPrefix)) {
            $title = $motionPrefix . '_' . $this->getFormattedTitlePrefix() . ' ' . $motionTitle;
        } else {
            $title = $this->getFormattedTitlePrefix() . ' ' . $motionTitle;
        }
        $filename = Tools::sanitizeFilename($title, $noUmlaut);

        return (grapheme_strlen($filename) > 59 ? (string)grapheme_substr($filename, 0, 59) : $filename);
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
                $return[$keyResolution] = Tools::formatMysqlDate($first->resolutionDate, false);
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
        if ($this->agendaItemId && $this->agendaItem) { // Only show this if an explicit agenda item was set
            $return[\Yii::t('export', 'AgendaItem')] = $this->agendaItem->getShownCode(true) . ' ' . $this->agendaItem->title;
        }

        $consultation = $this->getMyConsultation();
        if (in_array($this->status, $consultation->getStatuses()->getInvisibleMotionStatuses(false))) {
            $return[\Yii::t('motion', 'status')] = $consultation->getStatuses()->getStatusNames()[$this->status];
        }

        if ($this->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            if ($this->isProposalPublic() && $this->proposalStatus) {
                $return[\Yii::t('amend', 'proposed_status')] = strip_tags($this->getFormattedProposalStatus(true));
            }
        }

        return Layout::getAmendmentExportData($return, $this);
    }

    public function getMyMotionType(): ConsultationMotionType
    {
        return $this->getMyMotion()->getMyMotionType();
    }

    /**
     * @param array<int|string, int> $sectionMapping
     * @throws FormError
     */
    public function setMotionType(ConsultationMotionType $motionType, array $sectionMapping): void
    {
        foreach ($this->sections as $section) {
            if (!isset($sectionMapping[$section->sectionId])) {
                throw new FormError('This amendment cannot be changed to the type ' . $motionType->titleSingular . ': no complete section mapping found');
            }
        }

        $mySections  = $this->getSortedSections(false);
        for ($i = 0; $i < count($mySections); $i++) {
            $mySections[$i]->sectionId = $sectionMapping[$mySections[$i]->sectionId];
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
        if ($includeOtherAmendments && $this->proposalStatus === Amendment::STATUS_OBSOLETED_BY_AMENDMENT) {
            $obsoletedBy = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->hasAlternativeProposaltext($includeOtherAmendments, $internalNestingLevel + 1);
            }
        }

        // It was proposed to move this amendment to another motion
        if ($includeOtherAmendments && $this->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
            $movedTo = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
            if ($movedTo) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the modification proposed and the amendment to which the modification was directly proposed
     * (which has not to be this very amendment, in case this amendment is obsoleted by another amendment)
     *
     * @return array{amendment: Amendment, modification: Amendment}|null
     */
    public function getAlternativeProposaltextReference(int $internalNestingLevel = 0): ?array
    {
        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) && $this->getMyProposalReference()) {
            return [
                'amendment'    => $this,
                'modification' => $this->getMyProposalReference(),
            ];
        }

        // This amendment is obsoleted by an amendment with a modification proposal
        if ($this->proposalStatus === Amendment::STATUS_OBSOLETED_BY_AMENDMENT) {
            $obsoletedBy = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
            if ($obsoletedBy && $internalNestingLevel < 10) {
                return $obsoletedBy->getAlternativeProposaltextReference($internalNestingLevel + 1);
            }
        }

        // It was proposed to move this amendment to another motion
        if ($this->proposalStatus === Amendment::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION) {
            $movedTo = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
            if ($movedTo) {
                return [
                    'amendment'    => $this,
                    'modification' => $movedTo,
                ];
            }
        }

        return null;
    }

    /*
     * Global alternatives and withdrawn amendments are never selected.
     * For regular amendments, voting results always take precedence.
     * In absence of a voting, if no proposed procedure is set, the checkbox should always be preselected.
     * If there is one, it depends on if either the amendment, the proposed procedure or the vote was set as accepted,
     * or is set as "modified accepted".
     */
    public function markForMergingByDefault(bool $hasProposals): bool
    {
        if ($this->globalAlternative) {
            return false;
        }

        if (in_array($this->status, [static::STATUS_REJECTED, static::STATUS_WITHDRAWN])) {
            return false;
        }
        if (in_array($this->status, [static::STATUS_ACCEPTED, static::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION])) {
            return true;
        }
        if (in_array($this->status, [static::STATUS_VOTE, static::STATUS_SUBMITTED_SCREENED]) || $this->proposalStatus === static::STATUS_VOTE) {
            if ($this->votingStatus === static::STATUS_ACCEPTED) {
                return true;
            }
            if ($this->votingStatus === static::STATUS_REJECTED) {
                return false;
            }
        }

        if (!$hasProposals) {
            return true;
        }
        if ($this->proposalStatus === static::STATUS_ACCEPTED) {
            return true;
        }
        if (in_array($this->status, [static::STATUS_PROPOSED_MODIFIED_AMENDMENT, static::STATUS_PROPOSED_MODIFIED_MOTION]) ||
            $this->proposalStatus === static::STATUS_MODIFIED_ACCEPTED) {
            return true;
        }

        return false;
    }

    public function getFormattedStatus(): string
    {
        $statusNames = $this->getMyConsultation()->getStatuses()->getStatusNames();
        $status = '';
        $statusString = $this->statusString;

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
                $policy = $this->getMyMotionType()->getAmendmentSupportPolicy();
                $status .= $policy::getPolicyName() . ')</small>';
                break;
            case Amendment::STATUS_OBSOLETED_BY_MOTION:
                $othermot = $this->getMyConsultation()->getMotion(intval($this->statusString));
                if ($othermot) {
                    $status = \Yii::t('amend', 'obsoleted_by') . ': ';
                    $status .= Html::a(Html::encode($othermot->getTitleWithPrefix()), UrlHelper::createMotionUrl($othermot));
                    $statusString = null;
                } else {
                    $status .= Html::encode($statusNames[$this->status]);
                }
                break;
            case Amendment::STATUS_OBSOLETED_BY_AMENDMENT:
                $otheramend = $this->getMyConsultation()->getAmendment(intval($this->statusString));
                if ($otheramend) {
                    $status = \Yii::t('amend', 'obsoleted_by') . ': ';
                    $status .= Html::a(Html::encode($otheramend->getTitleWithPrefix()), UrlHelper::createAmendmentUrl($otheramend));
                    $statusString = null;
                } else {
                    $status .= Html::encode($statusNames[$this->status]);
                }
                break;
            default:
                $status .= Html::encode($statusNames[$this->status]);
        }
        if ($statusString) {
            $status .= ' <small>(' . Html::encode($statusString) . ')</small>';
        }

        return Layout::getFormattedAmendmentStatus($status, $this);
    }

    /**
     * @return Amendment[]
     * @throws Internal
     */
    public function collidesWithOtherProposedAmendments(bool $includeVoted): array
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
            if ($this->globalAlternative || $amendment->globalAlternative) {
                $collidesWith[] = $amendment;
                continue;
            }
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

    public function setVotingResult(int $votingResult): void
    {
        $this->votingStatus = $votingResult;
        if ($votingResult === IMotion::STATUS_ACCEPTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::AMENDMENT_VOTE_ACCEPTED, $this->id);
        }
        if ($votingResult === IMotion::STATUS_REJECTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::AMENDMENT_VOTE_REJECTED, $this->id);
        }
    }

    public function getUserdataExportObject(): array
    {
        $data = [
            'title'            => $this->getTitle(),
            'title_prefix'     => $this->getFormattedTitlePrefix(),
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

    public function getAgendaApiBaseObject(): array
    {
        if ($this->isProposalPublic()) {
            $procedure = Agenda::formatProposedProcedure($this, Agenda::FORMAT_HTML);
        } else {
            $procedure = null;
        }

        return [
            'type' => 'amendment',
            'id' => $this->id,
            'prefix' => $this->titlePrefix,
            'title_with_prefix' => $this->getTitleWithPrefix(),
            'url_json' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this, 'rest')),
            'url_html' => UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this)),
            'initiators_html' => $this->getInitiatorsStr(),
            'procedure' => $procedure,
            'item_group_same_vote' => $this->getVotingData()->itemGroupSameVote,
            'item_group_name' => $this->getVotingData()->itemGroupName,
            'voting_status' => $this->votingStatus,
        ];
    }
}
