<?php

namespace app\models\db;

use app\models\AdminTodoItem;
use app\models\db\repostory\{ConsultationRepository, MotionRepository};
use app\models\forms\MotionDeepCopy;
use app\models\proposedProcedure\Agenda;
use app\views\consultation\LayoutHelper;
use app\models\settings\{PrivilegeQueryContext, Privileges, AntragsgruenApp, MotionSection as MotionSectionSettings};
use app\models\notifications\{MotionProposedProcedure,
    MotionPublished,
    MotionSubmitted as MotionSubmittedNotification,
    MotionWithdrawn as MotionWithdrawnNotification,
    MotionEdited as MotionEditedNotification};
use app\components\{HashedStaticCache, IMotionStatusFilter, MotionSorter, RequestContext, RSSExporter, Tools, UrlHelper};
use app\models\exceptions\{FormError, Internal, NotAmendable, NotFound};
use app\models\layoutHooks\Layout;
use app\models\mergeAmendments\Draft;
use app\models\events\MotionEvent;
use app\models\sectionTypes\{Image, ISectionType, PDF};
use app\models\supportTypes\SupportBase;
use Symfony\Component\String\Slugger\AsciiSlugger;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * @property int|null $id
 * @property int $consultationId
 * @property int $motionTypeId
 * @property int|null $parentMotionId
 * @property string $title
 * @property string $titlePrefix
 * @property string $version
 * @property int $status
 * @property string $statusString
 * @property int $nonAmendable
 * @property int $notCommentable
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
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_PUBLISHED = 'published';
    public const EVENT_PUBLISHED_FIRST = 'published_first';
    public const EVENT_MERGED = 'merged'; // Called on the newly created motion

    public const VERSION_DEFAULT = '1';

    public function init(): void
    {
        parent::init();

        $this->on(static::EVENT_PUBLISHED, [$this, 'onPublish'], null, false);
        $this->on(static::EVENT_PUBLISHED_FIRST, [$this, 'onPublishFirst'], null, false);
        $this->on(static::EVENT_SUBMITTED, [$this, 'setInitialSubmitted'], null, false);
        $this->on(static::EVENT_MERGED, [$this, 'onMerged'], null, false);
    }

    public static function tableName(): string
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
        $viewCacheNeedsRebuild = !(count($this->dirtyAttributes) === 0 || array_keys($this->dirtyAttributes) === ['cache']);

        $result = parent::save($runValidation, $attributeNames);

        if ($viewCacheNeedsRebuild) {
            $this->flushViewCache();
        }

        return $result;
    }

    public function link($name, $model, $extraColumns = []): void
    {
        parent::link($name, $model, $extraColumns);
        $this->flushViewCache();
    }

    public function unlink($name, $model, $delete = false): void
    {
        parent::unlink($name, $model, $delete);
        $this->flushViewCache();
    }

    public function getComments(): ActiveQuery
    {
        return $this->hasMany(MotionComment::class, ['motionId' => 'id'])
                    ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED)
                    ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_PRIVATE);
    }

    public function getPrivateComments(): ActiveQuery
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
     * @return MotionAdminComment[]
     */
    public function getAdminComments(array $types, string $sort = 'desc', ?int $limit = null): array
    {
        /** @var MotionAdminComment[] $comments */
        $comments = MotionAdminComment::find()
                                 ->where(['motionId' => $this->id, 'status' => $types])
                                 ->orderBy(['dateCreation' => $sort])
                                 ->limit($limit)->all();
        return $comments;
    }

    /**
     * @return MotionAdminComment[]
     */
    public function getAllAdminComments(): array
    {
        /** @var MotionAdminComment[] $adminComments */
        $adminComments = MotionAdminComment::find()->where(['motionId' => $this->id])->all();
        return $adminComments;
    }

    public function getMotionSupporters(): ActiveQuery
    {
        return $this->hasMany(MotionSupporter::class, ['motionId' => 'id']);
    }

    public function getAmendments(): ActiveQuery
    {
        return $this->hasMany(Amendment::class, ['motionId' => 'id'])
                    ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(ConsultationSettingsTag::class, ['id' => 'tagId'])
                    ->viaTable('motionTag', ['motionId' => 'id']);
    }

    public function getSections(): ActiveQuery
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
        $foundSectionTypes = [];
        foreach ($this->sections as $section) {
            if (!$section->getSettings()) {
                // Internal problem - maybe an accidentally deleted motion type
                continue;
            }

            $foundSectionTypes[] = $section->getSettings()->id;

            if ($filterType !== null && $section->getSettings()->type !== $filterType) {
                continue;
            }
            if ($section->getSettings()->getSettingsObj()->public !== MotionSectionSettings::PUBLIC_YES && !$showAdminSections) {
                $hadNonPublicSections = true;
                continue;
            }

            $sections[] = $section;
        }

        foreach ($this->getTypeSections() as $typeSection) {
            if (!in_array($typeSection->id, $foundSectionTypes) && $typeSection->requiresAutoCreationWhenMissing()) {
                $emptySection = MotionSection::createEmpty($typeSection->id, $typeSection->getSettingsObj()->public, $this->id);
                $emptySection->save();

                $sections[] = $emptySection;
            }
        }

        if ($showAdminSections && $hadNonPublicSections && !$this->iAmInitiator() &&
            !User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
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

    public function getMotionType(): ActiveQuery
    {
        return $this->hasOne(ConsultationMotionType::class, ['id' => 'motionTypeId']);
    }

    public function getAgendaItem(): ActiveQuery
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    public function getReplacedMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'parentMotionId']);
    }

    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    public function getAssignedVotingBlocks(): ActiveQuery
    {
        return $this->hasMany(VotingBlock::class, ['assignedToMotionId' => 'id']);
    }

    public function getVotes(): ActiveQuery
    {
        return $this->hasMany(Vote::class, ['motionId' => 'id']);
    }

    public function getReplacedByMotions(): ActiveQuery
    {
        return $this->hasMany(Motion::class, ['parentMotionId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    public function getSpeechQueues(): ActiveQuery
    {
        return $this->hasMany(SpeechQueue::class, ['motionId' => 'id']);
    }

    public function getResponsibilityUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'responsibilityId']);
    }

    public function getMyConsultation(): ?Consultation
    {
        return ConsultationRepository::getConsultationById($this->consultationId);
    }

    public function getMyAgendaItem(): ?ConsultationAgendaItem
    {
        return ($this->agendaItemId ? $this->agendaItem : null);
    }

    /**
     * @return ConsultationSettingsMotionSection[]
     */
    public function getTypeSections(): array
    {
        return $this->getMyMotionType()->motionSections;
    }

    public function rules(): array
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
     * @return string ("Application: John <Doe>")
     */
    public function getTitleWithIntro(): string
    {
        try {
            $intro = $this->getMyMotionType()->getSettingsObj()->motionTitleIntro;
        } catch (\Exception $e) {
            $intro = '';
        }
        if (grapheme_strlen($intro) > 0 && grapheme_substr($intro, grapheme_strlen($intro) - 1, 1) !== ' ') {
            $intro .= ' ';
        }

        return $intro . $this->title;
    }

    public function showTitlePrefix(): bool
    {
        return (
            !$this->getMyConsultation()->getSettings()->hideTitlePrefix &&
            trim($this->getFormattedTitlePrefix()) !== '' &&
            !$this->isResolution()
        );
    }

    public function getTitleWithPrefix(): string
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->getTitleWithIntro();
        }

        $name = $this->getFormattedTitlePrefix();
        if (grapheme_strlen($name) > 1 && !in_array(grapheme_substr($name, grapheme_strlen($name) - 1, 1), [':', '.'])) {
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

    public function getFormattedVersion(): string
    {
        $default = \Yii::t('motion', 'version') . ' ' . $this->version;
        return Layout::getFormattedMotionVersion($default, $this);
    }

    /**
     * @return Amendment[]
     */
    public function getFilteredAmendments(IMotionStatusFilter $filter): array
    {
        return $filter->filterAmendments($this->amendments);
    }

    public function getFilteredAndSortedAmendments(IMotionStatusFilter $filter): array
    {
        $amendments = $this->getFilteredAmendments($filter);

        return MotionSorter::getSortedAmendments($this->getMyConsultation(), $amendments);
    }

    /**
     * @return Amendment[]
     */
    public function getVisibleAmendments(bool $includeWithdrawn = true): array
    {
        return $this->getFilteredAmendments(IMotionStatusFilter::onlyUserVisible($this->getMyConsultation(), $includeWithdrawn));
    }

    /**
     * @param null|Amendment[] $exclude
     *
     * @return Amendment[]
     */
    public function getAmendmentsRelevantForCollisionDetection(?array $exclude = null): array
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
    public function getVisibleAmendmentsSorted(bool $includeWithdrawn = true): array
    {
        $amendments = $this->getVisibleAmendments($includeWithdrawn);

        return MotionSorter::getSortedAmendments($this->getMyConsultation(), $amendments);
    }

    public function iAmInitiator(): bool
    {
        $user = RequestContext::getYiiUser();
        if ($user->isGuest) {
            return false;
        }

        foreach ($this->motionSupporters as $supp) {
            if ($supp->role === MotionSupporter::ROLE_INITIATOR && $supp->userId === $user->id) {
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

    public function canEditText(): bool
    {
        return $this->getPermissionsObject()->motionCanEditText($this);
    }

    public function canEditInitiators(): bool
    {
        return $this->getPermissionsObject()->motionCanEditInitiators($this);
    }

    public function canWithdraw(): bool
    {
        return $this->getPermissionsObject()->iMotionCanWithdraw($this);
    }

    public function canMergeAmendments(): bool
    {
        return $this->getPermissionsObject()->motionCanMergeAmendments($this);
    }

    public function canCreateResolution(): bool
    {
        return User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_MOTION_STATUS_EDIT, PrivilegeQueryContext::motion($this));
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
            if ($this->hasEnoughSupporters($this->getMyMotionType()->getMotionSupportTypeClass()) && !$supportSettings->allowMoreSupporters) {
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

    /**
     * @return Motion[]
     */
    public function getVisibleReplacedByMotions(bool $hideIfReplacedByResolution = true): array
    {
        $invisibleStatuses = $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses();
        if (!$hideIfReplacedByResolution) {
            $invisibleStatuses[] = IMotion::STATUS_RESOLUTION_FINAL;
            $invisibleStatuses[] = IMotion::STATUS_RESOLUTION_PRELIMINARY;
        }

        return $this->getReplacedByMotionsWithoutStatus($invisibleStatuses);
    }

    public function getReadableReplacedByMotions(): array
    {
        $unreadableStatuses = $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses();

        return $this->getReplacedByMotionsWithoutStatus($unreadableStatuses);
    }

    /**
     * @param int[] $filteredOutStatuses
     * @return Motion[]
     */
    private function getReplacedByMotionsWithoutStatus(array $filteredOutStatuses): array
    {
        $replacedByMotions = [];
        foreach ($this->replacedByMotions as $replMotion) {
            if (!in_array($replMotion->status, $filteredOutStatuses)) {
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

    public function getNumberOfCountableLines(): int
    {
        $cached = $this->getCacheItem('lines.getNumberOfCountableLines');
        if ($cached !== null) {
            return $cached;
        }

        $num = 0;
        foreach ($this->getSortedSections() as $section) {
            /** @var MotionSection $section */
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
            $motions = IMotionStatusFilter::onlyUserVisible($this->getMyConsultation(), false)
                                          ->getFilteredConsultationMotions();
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
        $motionSupportType = $this->getMyMotionType()->getMotionSupportTypeClass();

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
                $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId, $this->getPublicTopicTags());
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
            $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId, $this->getPublicTopicTags());
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
        HashedStaticCache::getInstance($this->getPdfCacheKey(), null)->setIsBulky(true)->flushCache();
        foreach ($this->amendments as $amend) {
            $amend->flushCacheWithChildren($items);
        }
        $this->flushViewCache();
    }

    public function flushViewCache(): void
    {
        HashedStaticCache::getInstance(\app\views\motion\LayoutHelper::getViewCacheKey($this), null)->setIsBulky(true)->flushCache();
        HashedStaticCache::getInstance($this->getPdfCacheKey(), null)->setIsBulky(true)->flushCache();
        MotionRepository::flushCaches();
        LayoutHelper::flushViewCaches($this->getMyConsultation());
        AdminTodoItem::flushConsultationTodoCount($this->getMyConsultation());
    }

    public function getPdfCacheKey(): string
    {
        return 'motion-pdf-' . $this->id;
    }

    public function getFilenameBase(bool $noUmlaut): string
    {
        $motionTitle = (grapheme_strlen($this->title) > 100 ? grapheme_substr($this->title, 0, 100) : $this->title);
        $title       = $this->getFormattedTitlePrefix() . ' ' . $motionTitle;

        return Tools::sanitizeFilename($title, $noUmlaut);
    }

    public function createSlug(): ?string
    {
        if (trim($this->title) === '') {
            return null;
        }

        $motionTitle = (grapheme_strlen($this->title) > 70 ? (string)grapheme_substr($this->title, 0, 70) : $this->title);
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
        if ($this->getFormattedTitlePrefix() && !$this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->getFormattedTitlePrefix();
        } else {
            return $this->getMyMotionType()->titleSingular;
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
                $return[\Yii::t('export', 'ResolutionDate')] = Tools::formatMysqlDate($resolutionDate, false);

                // For applications, the title usually is the name of the person -> no need to repeat the name
            } elseif (!$first->name || grapheme_stripos($this->title, $first->name) === false) {
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

        if ($this->getMyMotionType()->getSettingsObj()->showProposalsInExports) {
            if ($this->isProposalPublic() && $this->proposalStatus) {
                $return[\Yii::t('amend', 'proposed_status')] = strip_tags($this->getFormattedProposalStatus(true));
            }
        }

        return Layout::getMotionExportData($return, $this);
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
     * @param array<int|string, int> $sectionMapping
     * @throws FormError
     */
    public function setMotionType(ConsultationMotionType $motionType, array $sectionMapping): void
    {
        foreach ($this->sections as $section) {
            if (!isset($sectionMapping[$section->sectionId])) {
                throw new FormError($motionType->titleSingular . ': no complete section mapping found. Missing: ' . $section->sectionId);
            }
        }

        foreach ($this->amendments as $amendment) {
            $amendment->setMotionType($motionType, $sectionMapping);
        }

        /** @var MotionSection[] $mySections */
        $mySections = $this->getSortedSections(false);
        for ($i = 0; $i < count($mySections); $i++) {
            /** @var ConsultationSettingsMotionSection $newSection */
            $newSection = $motionType->getSectionById($sectionMapping[$mySections[$i]->sectionId]);
            if (!$mySections[$i]->overrideSectionId($newSection)) {
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
        $statusNames = $consultation->getStatuses()->getStatusNames();

        $statusString = $this->statusString;
        if ($this->isInScreeningProcess()) {
            $status .= '<span class="unscreened">' . Html::encode($statusNames[$this->status]) . '</span>';
        } elseif ($this->status === Motion::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
            $status .= '<span class="screened">' . \Yii::t('motion', 'screened_hint') . '</span>';
        } elseif ($this->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
            $status .= Html::encode($statusNames[$this->status]);
            $status .= ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
            $policy = $this->getMyMotionType()->getMotionSupportPolicy();
            $status .= $policy::getPolicyName() . ')</small>';
        } elseif ($this->status === Motion::STATUS_OBSOLETED_BY_MOTION) {
            $othermot = $this->getMyConsultation()->getMotion(intval($this->statusString));
            if ($othermot) {
                $status = \Yii::t('amend', 'obsoleted_by') . ': ';
                $status .= Html::a(Html::encode($othermot->getTitleWithPrefix()), UrlHelper::createMotionUrl($othermot));
                $statusString = null;
            } else {
                $status .= Html::encode($statusNames[$this->status]);
            }
        } elseif ($this->status === Motion::STATUS_OBSOLETED_BY_AMENDMENT) {
            $otheramend = $this->getMyConsultation()->getAmendment(intval($this->statusString));
            if ($otheramend) {
                $status = \Yii::t('amend', 'obsoleted_by') . ': ';
                $status .= Html::a(Html::encode($otheramend->getTitleWithPrefix()), UrlHelper::createAmendmentUrl($otheramend));
                $statusString = null;
            } else {
                $status .= Html::encode($statusNames[$this->status]);
            }
        } else {
            $status .= Html::encode($statusNames[$this->status]);
        }
        if ($statusString) {
            $status .= ' <small>(' . Html::encode($statusString) . ')</string>';
        }

        return Layout::getFormattedMotionStatus($status, $this);
    }

    public function getLikeDislikeSettings(): int
    {
        return $this->getMyMotionType()->motionLikesDislikes;
    }

    public function isDeadlineOver(): bool
    {
        return !$this->getMyMotionType()->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
    }

    public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool
    {
        return in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) &&
            $this->proposalReferenceId && $this->getMyConsultation()->getAmendment($this->proposalReferenceId);
    }

    /**
     * @return array{motion: Motion, modification: Amendment}|null
     */
    public function getAlternativeProposaltextReference(): ?array
    {
        // This amendment has a direct modification proposal
        if (in_array($this->proposalStatus, [Amendment::STATUS_MODIFIED_ACCEPTED, Amendment::STATUS_VOTE]) && $this->getMyProposalReference()) {
            return [
                'motion'    => $this,
                'modification' => $this->getMyProposalReference(),
            ];
        }

        return null;
    }

    // Hint: All statuses selectable except STATUS_VOTE
    public const FOLLOWABLE_PROPOSAL_STATUSES = [
        self::STATUS_MODIFIED_ACCEPTED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_REFERRED,
        self::STATUS_OBSOLETED_BY_MOTION,
        self::STATUS_OBSOLETED_BY_AMENDMENT,
        self::STATUS_CUSTOM_STRING,
    ];

    /**
     * @param MotionSupporter[] $newInitiators
     */
    public function followProposalAndCreateNewVersion(string $versionId, int $acceptStatus = self::STATUS_ACCEPTED, ?array $newInitiators = null): Motion
    {
        if (!in_array($this->proposalStatus, [self::STATUS_MODIFIED_ACCEPTED, self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_CUSTOM_STRING])) {
            throw new FormError('No proposal set');
        }

        $newVersion = MotionDeepCopy::copyMotion(
            $this,
            $this->getMyMotionType(),
            $this->agendaItem,
            $this->titlePrefix,
            $versionId,
            true
        );

        if ($newInitiators !== null) {
            foreach ($newVersion->motionSupporters as $supporter) {
                $supporter->delete();
            }
            foreach ($newInitiators as $newInitiator) {
                $newInitiator->motionId = $newVersion->id;
                $newInitiator->save();
            }
        }

        $newVersion->status = match ($this->proposalStatus) {
            self::STATUS_MODIFIED_ACCEPTED, self::STATUS_ACCEPTED => $acceptStatus,
            default => $this->proposalStatus,
        };
        if (in_array($this->proposalStatus, [self::STATUS_OBSOLETED_BY_MOTION, self::STATUS_OBSOLETED_BY_AMENDMENT])) {
            $newVersion->statusString = $this->proposalComment;
        }
        if ($this->proposalStatus === self::STATUS_CUSTOM_STRING) {
            $newVersion->statusString = $this->proposalComment;
        }
        if ($this->proposalStatus === self::STATUS_MODIFIED_ACCEPTED && ($modified = $this->getMyProposalReference())) {
            foreach ($newVersion->sections as $section) {
                $amendmentSection = $modified->getSection($section->sectionId);
                if ($amendmentSection) {
                    $section->setData($amendmentSection->getData());
                    $section->save();
                }
            }
        }
        $newVersion->proposalStatus = null;
        $newVersion->proposalComment = null;
        $newVersion->save();

        return $newVersion;
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
            'title_prefix'     => $this->getFormattedTitlePrefix(),
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

    public function getAgendaApiBaseObject(): array
    {
        if ($this->isProposalPublic()) {
            $procedure = Agenda::formatProposedProcedure($this, Agenda::FORMAT_HTML);
        } elseif ($this->status === IMotion::STATUS_MOVED) {
            $procedure = \app\views\consultation\LayoutHelper::getMotionMovedStatusHtml($this);
        } else {
            $procedure = null;
        }

        return [
            'type' => 'motion',
            'id' => $this->id,
            'prefix' => $this->titlePrefix,
            'title_with_prefix' => $this->getTitleWithPrefix(),
            'url_json' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this, 'rest')),
            'url_html' => UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this)),
            'initiators_html' => $this->getInitiatorsStr(),
            'procedure' => $procedure,
            'item_group_same_vote' => $this->getVotingData()->itemGroupSameVote,
            'item_group_name' => $this->getVotingData()->itemGroupName,
            'voting_status' => $this->votingStatus,
        ];
    }
}
