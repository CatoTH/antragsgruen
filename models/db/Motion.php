<?php

namespace app\models\db;

use app\models\notifications\{MotionProposedProcedure,
    MotionPublished,
    MotionSubmitted as MotionSubmittedNotification,
    MotionWithdrawn as MotionWithdrawnNotification,
    MotionEdited as MotionEditedNotification};
use app\models\settings\AntragsgruenApp;
use app\components\{HashedStaticFileCache, MotionSorter, RSSExporter, Tools, UrlHelper};
use app\models\exceptions\{FormError, Internal, NotAmendable, NotFound};
use app\models\layoutHooks\Layout;
use app\models\mergeAmendments\Draft;
use app\models\policies\IPolicy;
use app\models\events\MotionEvent;
use app\models\sectionTypes\{Image, ISectionType, PDF};
use app\models\settings\MotionSection as MotionSectionSettings;
use app\models\supportTypes\SupportBase;
use Symfony\Component\String\Slugger\AsciiSlugger;
use yii\helpers\Html;

/**
 * @property int|null $id
 * @property int $consultationId
 * @property int $motionTypeId
 * @property int $parentMotionId
 * @property string $title
 * @property string $titlePrefix
 * @property int $status
 * @property string $statusString
 * @property int $nonAmendable
 * @property string|null $noteInternal
 * @property string $cache
 * @property int $textFixed
 * @property string|null $slug
 * @property int|null $responsibilityId
 * @property string|null $responsibilityComment
 * @property string|null $extraData
 *
 * @property ConsultationMotionType $motionType
 * @property Amendment[] $amendments
 * @property MotionComment[] $comments
 * @property MotionComment[] $privateComments
 * @property MotionSection[] $sections
 * @property MotionSupporter[] $motionSupporters
 * @property Motion|null $replacedMotion
 * @property Motion[] $replacedByMotions
 * @property VotingBlock|null $votingBlock
 * @property User|null $responsibilityUser
 * @property SpeechQueue[] $speechQueues
 * @property Vote[] $votes
 * @property VotingBlock[] $assignedVotingBlocks
 */
class Motion extends IMotion implements IRSSItem
{
    const EVENT_SUBMITTED = 'submitted';
    const EVENT_PUBLISHED = 'published';
    const EVENT_PUBLISHED_FIRST = 'published_first';
    const EVENT_MERGED = 'merged'; // Called on the newly created motion

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
        return AntragsgruenApp::getInstance()->tablePrefix . 'motion';
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
        $this->flushViewCache();

        return $result;
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

    public function getPrivateComment(?int $sectionId, int $paragraphNo): ?MotionComment
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
     * @return MotionAdminComment[]
     */
    public function getAllAdminComments()
    {
        return MotionAdminComment::find()->where(['motionId' => $this->id])->all();
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
     * @return MotionSection[]
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

        if ($showAdminSections && $hadNonPublicSections && !$this->iAmInitiator() && !User::havePrivilege($this->getMyConsultation(), User::PRIVILEGE_CONTENT_EDIT)) {
            // @TODO Find a solution to edit motions before submitting when not logged in
            throw new Internal('Can only set showAdminSections for admins');
        }

        return $sections;
    }

    public function getAlternativePdfSection(): ?MotionSection
    {
        $section = $this->getActiveSections(ISectionType::TYPE_PDF_ALTERNATIVE);
        return (count($section) > 0 && $section[0]->getData() !== '' ? $section[0] : null);
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
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedVotingBlocks()
    {
        return $this->hasMany(VotingBlock::class, ['assignedToMotionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(Vote::class, ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplacedByMotions()
    {
        return $this->hasMany(Motion::class, ['parentMotionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSpeechQueues()
    {
        return $this->hasMany(SpeechQueue::class, ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResponsibilityUser()
    {
        return $this->hasOne(User::class, ['id' => 'responsibilityId']);
    }

    public function getMyConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getMotion($this->id)) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    public function getMyAgendaItem(): ?ConsultationAgendaItem
    {
        return ($this->agendaItemId ? $this->agendaItem : null);
    }

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    public function getTypeSections()
    {
        return $this->getMyMotionType()->motionSections;
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

    public function refreshTitle(): void
    {
        $this->refresh();
        $section = $this->getTitleSection();
        if ($section) {
            $this->title = $section->getData();
        } else {
            $this->title = '';
        }
    }

    /**
     * @return Motion[]
     */
    public static function getNewestByConsultation(Consultation $consultation, int $limit = 5): array
    {
        $invisibleStatuses = array_map('intval', $consultation->getStatuses()->getInvisibleMotionStatuses());

        $query = Motion::find();
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStatuses) . ')');
        $query->andWhere('motion.consultationId = ' . intval($consultation->id));
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
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

        return $query->all();
    }

    /**
     * @return string ("Application: John <Doe>")
     */
    public function getTitleWithIntro(): string
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

    public function getTitleWithPrefix(): string
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->getTitleWithIntro();
        }

        $name = $this->titlePrefix;
        if (mb_strlen($name) > 1 && !in_array(mb_substr($name, mb_strlen($name) - 1, 1), [':', '.'])) {
            $name .= ':';
        }
        $name .= ' ' . $this->getTitleWithIntro();

        return $name; // unencoded string, e.g. "A1: Application: John <Doe>"
    }

    public function getEncodedTitleWithPrefix(): string
    {
        $title = $this->getTitleWithPrefix();
        $title = Html::encode($title);
        $title = str_replace(" - \n", "<br>", $title);
        $title = str_replace("\n", "<br>", $title);

        return $title; // encoded string, e.g. "A1: Application: John &lt;Doe&gt;"
    }

    /**
     * @return string ("A1new")
     */
    public function getNewTitlePrefix(): string
    {
        return static::getNewTitlePrefixInternal($this->titlePrefix);
    }

    /**
     * @return Amendment[]
     */
    public function getVisibleAmendments(bool $includeWithdrawn = true, bool $ifMotionIsMoved = true): array
    {
        if (!$ifMotionIsMoved && $this->status === Motion::STATUS_MOVED) {
            return [];
        }

        $filtered   = $this->getMyConsultation()->getStatuses()->getInvisibleAmendmentStatuses($includeWithdrawn);
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
     * @param null|int[] $exclude
     * @return Amendment[]
     */
    public function getAmendmentsProposedToBeIncluded(bool $includeVoted, ?array $exclude = null): array
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
     * @return Amendment[]
     */
    public function getVisibleAmendmentsSorted(bool $includeWithdrawn = true, bool $ifMotionIsMoved = true): array
    {
        $amendments = $this->getVisibleAmendments($includeWithdrawn, $ifMotionIsMoved);

        return MotionSorter::getSortedAmendments($this->getMyConsultation(), $amendments);
    }

    public function iAmInitiator(): bool
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

    public function canSeeProposedProcedure(?string $procedureToken): bool
    {
        if ($procedureToken && MotionProposedProcedure::getPpOpenAcceptToken($this) === $procedureToken) {
            return true;
        }
        return $this->iAmInitiator();
    }

    public function canEdit(): bool
    {
        return $this->getPermissionsObject()->motionCanEdit($this);
    }

    public function canWithdraw(): bool
    {
        return $this->getPermissionsObject()->motionCanWithdraw($this);
    }

    public function canMergeAmendments(): bool
    {
        return $this->getPermissionsObject()->motionCanMergeAmendments($this);
    }

    public function canCreateResolution(): bool
    {
        return User::havePrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT);
    }

    public function canFinishSupportCollection(): bool
    {
        $supportType = $this->getMyMotionType()->getMotionSupportTypeClass();

        return $this->getPermissionsObject()->canFinishSupportCollection($this, $supportType);
    }

    /**
     * @throws NotAmendable
     * @throws Internal
     */
    public function isCurrentlyAmendable(bool $allowAdmins = true, bool $assumeLoggedIn = false, bool $throwExceptions = false): bool
    {
        $permissions = $this->getPermissionsObject();

        return $permissions->isCurrentlyAmendable($this, $allowAdmins, $assumeLoggedIn, $throwExceptions);
    }

    public function isSupportingPossibleAtThisStatus(): bool
    {
        if (!($this->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_SUPPORT)) {
            return false;
        }
        $supportSettings = $this->getMyMotionType()->getMotionSupporterSettings();
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

    /**
     * @return Motion[]
     */
    public function getVisibleReplacedByMotions(): array
    {
        $replacedByMotions = [];
        foreach ($this->replacedByMotions as $replMotion) {
            if (!in_array($replMotion->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses())) {
                $replacedByMotions[] = $replMotion;
            }
        }

        return $replacedByMotions;
    }

    public function getMergingDraft(bool $onlyPublic): ?Draft
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

            return Draft::initFromJson($this, $public, $motion->getDateTime(), $motion->getActiveSections()[0]->dataRaw);
        } else {
            return null;
        }
    }

    public function getMergingUnconfirmed(): ?Motion
    {
        return Motion::findOne([
            'parentMotionId' => $this->id,
            'status'         => Motion::STATUS_DRAFT,
        ]);
    }

    public function isResolution(): bool
    {
        return in_array($this->status, [static::STATUS_RESOLUTION_FINAL, static::STATUS_RESOLUTION_PRELIMINARY]);
    }

    public function getIconCSSClass(): string
    {
        foreach ($this->getPublicTopicTags() as $tag) {
            return $tag->getCSSIconClass();
        }

        return 'glyphicon glyphicon-file';
    }

    public function getNumberOfCountableLines(): int
    {
        $cached = $this->getCacheItem('lines.getNumberOfCountableLines');
        if ($cached !== null) {
            return $cached;
        }

        $num = 0;
        foreach ($this->getSortedSections() as $section) {
            $num += $section->getNumberOfCountableLines();
        }

        $this->setCacheItem('lines.getNumberOfCountableLines', $num);

        return $num;
    }

    public function getFirstLineNumber(): int
    {
        $cached = $this->getCacheItem('lines.getFirstLineNumber');
        if ($cached !== null) {
            return $cached;
        }

        if ($this->getMyConsultation()->getSettings()->lineNumberingGlobal) {
            $motions      = $this->getMyConsultation()->getVisibleMotions(false);
            $motionBlocks = MotionSorter::getSortedIMotions($this->getMyConsultation(), $motions);
            $lineNo       = 1;
            foreach ($motionBlocks as $motions) {
                foreach ($motions as $motion) {
                    /** @var Motion $motion */
                    if ($motion->id === $this->id) {
                        $this->setCacheItem('lines.getFirstLineNumber', $lineNo);

                        return $lineNo;
                    } else {
                        $lineNo += $motion->getNumberOfCountableLines();
                    }
                }
            }

            // This is a invisible motion. The final line numbers are therefore not determined yet
            return 1;
        } else {
            $this->setCacheItem('lines.getFirstLineNumber', 1);

            return 1;
        }
    }

    /**
     * @return MotionSupporter[]
     */
    public function getInitiators(): array
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        }
        usort($return, function(MotionSupporter $supp1, MotionSupporter $supp2) {
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
     * @return MotionSupporter[]
     */
    public function getSupporters(bool $includeNonPublic = false): array
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_SUPPORTER) {
                if ($includeNonPublic || !$supp->isNonPublic()) {
                    $return[] = $supp;
                }
            }
        }
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
    public function getLikes(): array
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        }

        return $return;
    }

    /**
     * @return MotionSupporter[]
     */
    public function getDislikes(): array
    {
        $return = [];
        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        }

        return $return;
    }

    public function withdraw(): void
    {
        if ($this->status === Motion::STATUS_DRAFT) {
            $this->status = static::STATUS_DELETED;
        } elseif (in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses())) {
            $this->status = static::STATUS_WITHDRAWN_INVISIBLE;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->flushCacheStart(['lines']);
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_WITHDRAW, $this->id);
        new MotionWithdrawnNotification($this);
    }

    public function needsCollectionPhase(): bool
    {
        $motionSupportType = $this->motionType->getMotionSupportTypeClass();

        return $this->iNeedsCollectionPhase($motionSupportType);
    }

    public function getSubmitButtonLabel(): string
    {
        if ($this->needsCollectionPhase()) {
            return \Yii::t('motion', 'button_submit_create');
        } elseif ($this->getMyConsultation()->getSettings()->screeningMotions) {
            return \Yii::t('motion', 'button_submit_submit');
        } else {
            return \Yii::t('motion', 'button_submit_publish');
        }
    }

    public function setInitialSubmitted(): void
    {
        if ($this->needsCollectionPhase()) {
            $this->status = Motion::STATUS_COLLECTING_SUPPORTERS;
        } elseif ($this->getMyConsultation()->getSettings()->screeningMotions) {
            $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        } else {
            $this->status = Motion::STATUS_SUBMITTED_SCREENED;
            if ($this->titlePrefix === '' && !$this->getMyMotionType()->amendmentsOnly) {
                $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId);
            }
        }
        $this->dateCreation = date('Y-m-d H:i:s');
        $this->save();

        new MotionSubmittedNotification($this);
    }

    public function setScreened(): void
    {
        $this->status = Motion::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix === '' && !$this->getMyMotionType()->amendmentsOnly) {
            $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId);
        }
        $this->save(true);
        $this->trigger(Motion::EVENT_PUBLISHED, new MotionEvent($this));
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_SCREEN, $this->id);
    }

    public function setUnscreened(): void
    {
        $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_UNSCREEN, $this->id);
    }

    public function setProposalPublished(): void
    {
        if ($this->proposalVisibleFrom) {
            return;
        }
        $this->proposalVisibleFrom = date('Y-m-d H:i:s');
        $this->save();

        $consultation = $this->getMyConsultation();
        ConsultationLog::logCurrUser($consultation, ConsultationLog::MOTION_PUBLISH_PROPOSAL, $this->id);
    }

    public function setDeleted(): void
    {
        $this->status = Motion::STATUS_DELETED;
        $this->slug   = null;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_DELETE, $this->id);
    }

    public function isDeleted(): bool
    {
        if ($this->status === Motion::STATUS_DELETED) {
            return true;
        }
        if (!$this->getMyConsultation()) {
            return true;
        }

        return false;
    }

    public function onMerged(): void
    {
        if ($this->datePublication === null && $this->status === Motion::STATUS_SUBMITTED_SCREENED) {
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            new MotionEditedNotification($this);
        }
    }

    public function onPublish(): void
    {
        $this->flushCache(true);
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

    public function onPublishFirst(): void
    {
        UserNotification::notifyNewMotion($this);
        new MotionPublished($this);
    }

    public function setTextFixedIfNecessary(bool $save = true): void
    {
        if ($this->getMyConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses())) {
            return;
        }
        $this->textFixed = 1;
        if ($save) {
            $this->save(true);
        }
    }

    public function flushCacheStart(?array $items): void
    {
        if ($this->getMyConsultation()->cacheOneMotionAffectsOthers()) {
            $this->getMyConsultation()->flushCacheWithChildren($items);
        } else {
            $this->flushCacheWithChildren($items);
        }
    }

    public function flushCacheWithChildren(?array $items): void
    {
        if ($items) {
            $this->flushCacheItems($items);
        } else {
            $this->flushCache();
        }
        HashedStaticFileCache::flushCache($this->getPdfCacheKey(), null);
        foreach ($this->amendments as $amend) {
            $amend->flushCacheWithChildren($items);
        }
        $this->flushViewCache();
    }

    public function flushViewCache(): void
    {
        HashedStaticFileCache::flushCache(\app\views\motion\LayoutHelper::getViewCacheKey($this), null);
        HashedStaticFileCache::flushCache($this->getPdfCacheKey(), null);
    }

    public function getPdfCacheKey(): string
    {
        return 'motion-pdf-' . $this->id;
    }

    public function getFilenameBase(bool $noUmlaut): string
    {
        $motionTitle = (mb_strlen($this->title) > 100 ? mb_substr($this->title, 0, 100) : $this->title);
        $title       = $this->titlePrefix . ' ' . $motionTitle;

        return Tools::sanitizeFilename($title, $noUmlaut);
    }

    public function createSlug(): string
    {
        $motionTitle = (mb_strlen($this->title) > 70 ? mb_substr($this->title, 0, 70) : $this->title);
        $title = (new AsciiSlugger())->slug($motionTitle);

        /** @noinspection PhpUnhandledExceptionInspection */
        $random = \Yii::$app->getSecurity()->generateRandomKey(2);
        $random = ord($random[0]) + ord($random[1]) * 256;

        return $title . '-' . $random;
    }

    public function getMotionSlug(): string
    {
        if ($this->slug) {
            return $this->slug;
        } else {
            return (string)$this->id;
        }
    }

    public function getBreadcrumbTitle(): string
    {
        if ($this->titlePrefix && !$this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix;
        } else {
            return $this->motionType->titleSingular;
        }
    }

    public function addToFeed(RSSExporter $feed): void
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

    public function getDataTable(): array
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
        if ($this->agendaItemId && $this->agendaItem) {
            $return[\Yii::t('export', 'AgendaItem')] = $this->agendaItem->getShownCode(true) . ' ' . $this->agendaItem->title;
        }

        $tags = $this->getPublicTopicTags();
        if (count($tags) > 1) {
            $tagTitles = [];
            foreach ($tags as $tag) {
                $tagTitles[] = $tag->title;
            }
            $return[\Yii::t('export', 'TopicMulti')] = implode("\n", $tagTitles);
        } elseif (count($tags) === 1) {
            $return[\Yii::t('export', 'TopicSingle')] = $tags[0]->title;
        }

        $consultation = $this->getMyConsultation();
        if (in_array($this->status, $consultation->getStatuses()->getInvisibleMotionStatuses(false))) {
            $return[\Yii::t('motion', 'status')] = $consultation->getStatuses()->getStatusNames()[$this->status];
        }

        return $return;
    }

    public function findAmendmentWithPrefix(string $prefix, ?Amendment $ignore = null): ?Amendment
    {
        $numbering = $this->getMyConsultation()->getAmendmentNumbering();

        return $numbering->findAmendmentWithPrefix($this, $prefix, $ignore);
    }

    public function getMyMotionType(): ConsultationMotionType
    {
        try {
            return $this->getMyConsultation()->getMotionType($this->motionTypeId);
        } catch (NotFound $e) {
            return $this->motionType;
        }
    }

    /**
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
            if (!$mySections[$i]->overrideSectionId($motionType->motionSections[$i])) {
                $err = print_r($mySections[$i]->getErrors(), true);
                throw new FormError('Something terrible happened while changing the motion type: ' . $err);
            }
        }

        $this->motionTypeId = $motionType->id;
        $this->save();
        $this->refresh();
    }

    public function getFormattedStatus(): string
    {
        $status = '';

        $consultation = $this->getMyConsultation();
        $screeningMotionsShown = $consultation->getSettings()->screeningMotionsShown;
        $statusNames           = $consultation->getStatuses()->getStatusNames();
        if ($this->isInScreeningProcess()) {
            $status .= '<span class="unscreened">' . Html::encode($statusNames[$this->status]) . '</span>';
        } elseif ($this->status === Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
            $status .= '<span class="screened">' . \Yii::t('motion', 'screened_hint') . '</span>';
        } elseif ($this->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
            $status .= Html::encode($statusNames[$this->status]);
            $status .= ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
            $status .= IPolicy::getPolicyNames()[$this->getMyMotionType()->policySupportMotions] . ')</small>';
        } else {
            $status .= Html::encode($statusNames[$this->status]);
        }
        if ($this->statusString !== null && trim($this->statusString) !== '') {
            $status .= ' <small>(' . Html::encode($this->statusString) . ')</string>';
        }

        return Layout::getFormattedMotionStatus($status, $this);
    }

    public function getLikeDislikeSettings(): int
    {
        return $this->motionType->motionLikesDislikes;
    }

    public function isDeadlineOver(): bool
    {
        return !$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
    }

    public function getLink(bool $absolute = false): string
    {
        $url = UrlHelper::createMotionUrl($this);
        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    public function getActiveSpeechQueue(): ?SpeechQueue
    {
        return $this->getMyConsultation()->getActiveSpeechQueue();
    }

    public function setVotingResult(int $votingResult): void
    {
        $this->votingStatus = $votingResult;
        if ($votingResult === IMotion::STATUS_ACCEPTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::MOTION_VOTE_ACCEPTED, $this->id);
        }
        if ($votingResult === IMotion::STATUS_REJECTED) {
            ConsultationLog::log($this->getMyConsultation(), null, ConsultationLog::MOTION_VOTE_REJECTED, $this->id);
        }
    }

    public function getUserdataExportObject(): array
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
            $type = $section->getSettings()->type;
            if ($type === ISectionType::TYPE_IMAGE) {
                /** @var Image $type */
                $type               = $section->getSectionType();
                $data['sections'][] = [
                    'section_title' => $section->getSettings()->title,
                    'section_type'  => $section->getSettings()->type,
                    'download_url'  => $type->getImageUrl(true),
                    'metadata'      => $section->metadata,
                ];
            } elseif ($type === ISectionType::TYPE_PDF_ATTACHMENT || $type === ISectionType::TYPE_PDF_ALTERNATIVE) {
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
                    'data'          => $section->getData(),
                    'metadata'      => $section->metadata,
                ];
            }
        }

        return $data;
    }
}
