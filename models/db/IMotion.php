<?php

namespace app\models\db;

use app\models\layoutHooks\Layout;
use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\consultationLog\ProposedProcedureChange;
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use app\models\quorumType\IQuorumType;
use app\models\sectionTypes\ISectionType;
use app\models\settings\{MotionSection as MotionSectionSettings, AntragsgruenApp, Permissions, PrivilegeQueryContext, Privileges};
use app\models\supportTypes\SupportBase;
use app\models\votings\VotingItemGroup;
use app\views\consultation\LayoutHelper;
use yii\base\InvalidConfigException;
use yii\db\{ActiveQueryInterface, ActiveRecord};
use yii\helpers\Html;

/**
 * @property string $titlePrefix
 * @property int $id
 * @property int|null $agendaItemId
 * @property IMotionSection[] $sections
 * @property string $dateCreation
 * @property string|null $datePublication
 * @property string|null $dateResolution
 * @property string $dateContentModification
 * @property IComment[] $comments
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationAgendaItem|null $agendaItem
 * @property int $status
 * @property string $statusString
 * @property int $notCommentable
 * @property int|null $proposalStatus
 * @property int|null $proposalReferenceId
 * @property string|null $proposalVisibleFrom
 * @property string|null $proposalComment
 * @property string|null $proposalNotification
 * @property int|null $proposalUserStatus
 * @property string|null $proposalExplanation
 * @property int|null $votingBlockId
 * @property string|null $votingData
 * @property int|null $votingStatus
 * @property int|null $responsibilityId
 * @property string|null $responsibilityComment
 * @property string|null $extraData
 * @property User|null $responsibilityUser
 * @property VotingBlock|null $votingBlock
 */
abstract class IMotion extends ActiveRecord implements IVotingItem
{
    use CacheTrait;
    use VotingItemTrait;

    // The motion has been deleted and is not visible anymore. Only admins can delete a motion.
    public const STATUS_DELETED = -2;

    // The motion has been withdrawn, either by the user or the admin.
    public const STATUS_WITHDRAWN = -1;
    public const STATUS_WITHDRAWN_INVISIBLE = -3;

    // The user has written the motion, but not yet confirmed to submit it.
    public const STATUS_DRAFT = 1;

    // The user has submitted the motion, but it's not yet visible. It's up to the admin to screen it now.
    public const STATUS_SUBMITTED_UNSCREENED = 2;
    public const STATUS_SUBMITTED_UNSCREENED_CHECKED = 18;

    // The default state once the motion is visible
    public const STATUS_SUBMITTED_SCREENED = 3;

    // These are statuses motions and amendments get as their final state.
    // "Processed" is mostly used for amendments after merging amendments into th motion,
    // if it's unclear if it was adopted or rejected.
    // For member petitions, "Processed" means the petition has been replied.
    public const STATUS_ACCEPTED = 4;
    public const STATUS_REJECTED = 5;
    public const STATUS_MODIFIED_ACCEPTED = 6;
    public const STATUS_PROCESSED = 17;
    public const STATUS_QUORUM_MISSED = 29;
    public const STATUS_QUORUM_REACHED = 30;

    // This is the reply to a motion / member petition and is to be shown within the parent motion view.
    public const STATUS_INLINE_REPLY = 24;

    // The initiator is still collecting supporters to actually submit this motion.
    // It's visible only to those who know the link to it.
    public const STATUS_COLLECTING_SUPPORTERS = 15;

    // Not yet visible, it's up to the admin to submit it
    public const STATUS_DRAFT_ADMIN = 16;

    // Saved drafts while merging amendments into an motion
    public const STATUS_MERGING_DRAFT_PUBLIC = 19;
    public const STATUS_MERGING_DRAFT_PRIVATE = 20;

    // The modified version of an amendment, as proposed by the admins.
    // This amendment is being referenced by proposalReference of the modified amendment.
    public const STATUS_PROPOSED_MODIFIED_AMENDMENT = 21;

    // The modified version of a motion, as proposed by the admins.
    // This amendment is being referenced by proposalReference of the modified motion.
    public const STATUS_PROPOSED_MODIFIED_MOTION = 31;

    // Used as a status for amendment, which is the proposed move of an amendment to another motion.
    // The original amendment gets this status as `proposalStatus`, the internal new amendment (for the other motion) gets this status as `status`.
    // The internal new amendment should not be used-visible in the context of its motion (only when merging),
    // only within the amendment that references this one via its proposalReference
    public const STATUS_PROPOSED_MOVE_TO_OTHER_MOTION = 28;

    // An amendment or motion has been referred to another institution.
    // The institution is documented in statusString, or, in case of a change proposal, in proposalComment
    public const STATUS_REFERRED = 10;

    // The motion still exists at the original place, but has been replaced by a copy at another consultation or agenda item.
    // This motion is referenced by the new motion as parentMotionId.
    // Amendments cannot be moved, they are always sticked to the motion.
    public const STATUS_MOVED = 27;

    // A motion/amendment becomes obsoleted by another one. That one is referred by an id
    // in statusString (a bit unelegantely), or, in case of a change proposal, in proposalComment
    public const STATUS_OBSOLETED_BY_AMENDMENT = 22;
    public const STATUS_OBSOLETED_BY_MOTION = 32;

    // The exact status is specified in a free-text field; proposalComment if this status is used in proposalStatus
    public const STATUS_CUSTOM_STRING = 23;

    // The version of a motion that the convention has agreed upon
    public const STATUS_RESOLUTION_PRELIMINARY = 25;
    public const STATUS_RESOLUTION_FINAL = 26;

    // A new version of this motion exists that should be shown instead. Not visible on the home page.
    public const STATUS_MODIFIED = 7;

    // Purely informational statuses
    public const STATUS_ADOPTED = 8;
    public const STATUS_COMPLETED = 9;
    public const STATUS_VOTE = 11;
    public const STATUS_PAUSED = 12;
    public const STATUS_MISSING_INFORMATION = 13;
    public const STATUS_DISMISSED = 14;

    public function isInScreeningProcess(): bool
    {
        return in_array($this->status, $this->getMyConsultation()->getStatuses()->getScreeningStatuses());
    }

    public function isSubmitted(): bool
    {
        return !in_array($this->status, $this->getMyConsultation()->getStatuses()->getNotYetSubmittedStatuses());
    }

    /**
     * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
     *
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface|ActiveQuery]] instance.
     * @throws InvalidConfigException if there is no primary key defined
     * @internal
     */
    protected static function findByCondition($condition)
    {
        $query = parent::findByCondition($condition);
        $query->andWhere('status != ' . self::STATUS_DELETED);

        return $query;
    }

    public function getPermissionsObject(): Permissions
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $permissions = $plugin::getPermissionsClass();
            if ($permissions) {
                /** @var Permissions $permissionObj */
                $permissionObj = new $permissions();
                return $permissionObj;
            }
        }

        return new Permissions();
    }

    /**
     * @return ConsultationSettingsTag[]
     */
    public function getPublicTopicTags(): array
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            if ($tag->type === ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }

    /**
     * @return ConsultationSettingsTag[]
     */
    public function getProposedProcedureTags(): array
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            if ($tag->type === ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE) {
                $tags[$tag->getNormalizedName()] = $tag;
            }
        }
        return $tags;
    }

    /**
     * @param int[] $newList
     */
    public function setTags(int $type, array $newList): void
    {
        foreach ($this->getMyConsultation()->tags as $tag) {
            if ($tag->type !== $type) {
                continue;
            }
            if (in_array($tag->id, $newList)) {
                try {
                    $this->link('tags', $tag);
                } catch (\Exception $e) {
                }
            } else {
                $this->unlink('tags', $tag, true);
            }
        }
    }

    /**
     * @param string[] $newList
     */
    public function setProposedProcedureTags(array $newList, ProposedProcedureChange $ppChanges): void
    {
        $oldTags = $this->getProposedProcedureTags();
        $newTags = [];
        $changed = false;
        foreach ($newList as $newTag) {
            $tag = $this->getMyConsultation()->getExistingTagOrCreate(ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE, $newTag, 0);
            if (!isset($oldTags[$tag->getNormalizedName()])) {
                $this->link('tags', $tag);
                $changed = true;
            }
            $newTags[] = ConsultationSettingsTag::normalizeName($newTag);
        }
        foreach ($oldTags as $tagKey => $tag) {
            if (!in_array($tagKey, $newTags)) {
                $this->unlink('tags', $tag, true);
                $changed = true;
            }
        }
        if ($changed) {
            $ppChanges->setProposalTagsHaveChanged(array_keys($oldTags), $newTags);
        }
    }

    public function isVisible(): bool
    {
        if (!$this->getMyConsultation()) {
            return false;
        }

        return !in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses());
    }

    public function isVisibleForAdmins(): bool
    {
        return
            $this->isReadable() &&
            !in_array($this->status, $this->getMyConsultation()->getStatuses()->getStatusesInvisibleForAdmins());
    }

    public function isVisibleForProposalAdmins(): bool
    {
        return (
            $this->isVisibleForAdmins() &&
            !in_array($this->status, [
                self::STATUS_DRAFT,
                self::STATUS_DRAFT_ADMIN,
            ])
        );
    }

    public function isResolution(): bool
    {
        return in_array($this->status, [static::STATUS_RESOLUTION_FINAL, static::STATUS_RESOLUTION_PRELIMINARY]);
    }

    public function isProposalPublic(): bool
    {
        if (!$this->proposalVisibleFrom) {
            return false;
        }
        $visibleFromTs = Tools::dateSql2timestamp($this->proposalVisibleFrom);

        return ($visibleFromTs <= time());
    }

    public function isReadable(): bool
    {
        return $this->getPermissionsObject()->iMotionIsReadable($this);
    }

    abstract public function setDeleted(): void;

    abstract public function isDeleted(): bool;

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators(): array;

    abstract public function iAmInitiator(): bool;

    abstract public function showTitlePrefix(): bool;

    public function getFormattedTitlePrefix(?int $context = null): ?string
    {
        return Layout::getFormattedTitlePrefix($this->titlePrefix, $this, $context);
    }

    abstract public function getTitleWithPrefix(): string;

    public function isInitiatedByOrganization(): bool
    {
        $cached = $this->getCacheItem('supporters.initiatedByOrga');
        if ($cached !== null) {
            return $cached;
        }

        $orgaInitiated = false;
        foreach ($this->getInitiators() as $initiator) {
            if ($initiator->personType === ISupporter::PERSON_ORGANIZATION) {
                $orgaInitiated = true;
            }
        }

        $this->setCacheItem('supporters.initiatedByOrga', $orgaInitiated);

        return $orgaInitiated;
    }

    /**
     * @param ISupporter[] $initiators
     */
    public function getInitiatorsStrFromArray(array $initiators): string
    {
        $str   = [];
        foreach ($initiators as $init) {
            $str[] = $init->getNameWithResolutionDate(false);
        }

        return implode(', ', $str);
    }

    public function getInitiatorsStr(): string
    {
        $cached = $this->getCacheItem('supporters.initiatorStr');
        if ($cached !== null) {
            return $cached;
        }

        $initiators = $this->getInitiators();
        $initiatorsStr = $this->getInitiatorsStrFromArray($initiators);
        $this->setCacheItem('supporters.initiatorStr', $initiatorsStr);

        return $initiatorsStr; // Hint: the returned string is NOT yet HTML-encoded
    }

    public function onSupportersChanged(): void
    {
        $this->flushCacheItems(['supporters']);
    }

    /**
     * @return ISupporter[]
     */
    abstract public function getSupporters(bool $includeNonPublic = false): array;

    /**
     * @return ISupporter[]
     */
    abstract public function getLikes(): array;

    /**
     * @return ISupporter[]
     */
    abstract public function getDislikes(): array;

    abstract public function getMyConsultation(): ?Consultation;

    abstract public function getMyAgendaItem(): ?ConsultationAgendaItem;

    /** @return ConsultationSettingsMotionSection[] */
    abstract public function getTypeSections(): array;

    /**
     * @return IMotionSection[]
     */
    abstract public function getActiveSections(?int $filterType = null, bool $showAdminSections = false): array;

    public function getTitleSection(): ?IMotionSection
    {
        foreach ($this->sections as $section) {
            if ($section->getSettings() && $section->getSettings()->type === ISectionType::TYPE_TITLE) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @return IMotionSection[]
     */
    public function getSortedSections(bool $withoutTitle = false, bool $includeNonPublicIfPossible = false): array
    {
        if ($includeNonPublicIfPossible &&
            ($this->iAmInitiator() ||User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CONTENT_EDIT, null))) {
            $includeNonPublic = true;
        } else {
            $includeNonPublic = false;
        }

        $sectionsIn = [];
        $title      = $this->getTitleSection();
        foreach ($this->getActiveSections(null, $includeNonPublic) as $section) {
            if (!$withoutTitle || $section !== $title) {
                $sectionsIn[$section->sectionId] = $section;
            }
        }
        /** @var MotionSection[] $sectionsOut */
        $sectionsOut = [];
        foreach ($this->getTypeSections() as $section) {
            if (isset($sectionsIn[$section->id])) {
                $sectionsOut[] = $sectionsIn[$section->id];
            }
        }

        return $sectionsOut;
    }

    public function hasNonPublicSections(): bool
    {
        foreach ($this->sections as $section) {
            if ($section->getSettings() === null) {
                continue;
            }
            if ($section->public !== MotionSectionSettings::PUBLIC_YES || $section->getSettings()->getSettingsObj()->public !== MotionSectionSettings::PUBLIC_YES) {
                return true;
            }
        }
        return false;
    }

    abstract public function getMyMotionType(): ConsultationMotionType;

    abstract public function getLikeDislikeSettings(): int;

    abstract public function isDeadlineOver(): bool;

    abstract public function getLink(bool $absolute = false): string;

    abstract public function getFilenameBase(bool $noUmlaut): string;

    public function getDate(): string
    {
        return $this->dateCreation;
    }

    public function getDateTime(): ?\DateTime
    {
        if ($this->dateCreation) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateCreation) ?: null;
        } else {
            return null;
        }
    }

    public function getPublicationDateTime(): ?\DateTime
    {
        if ($this->datePublication) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->datePublication) ?: null;
        } else {
            return null;
        }
    }

    public function getTimestamp(): int
    {
        if ($this->datePublication) {
            return Tools::dateSql2timestamp($this->datePublication);
        } elseif ($this->dateCreation) {
            return Tools::dateSql2timestamp($this->dateCreation);
        } else {
            return 0;
        }
    }

    public function wasContentEdited(): bool
    {
        $tsRef = $this->getTimestamp();
        $tsMod = Tools::dateSql2timestamp($this->dateContentModification);

        return $tsMod > $tsRef;
    }

    abstract public function isSupportingPossibleAtThisStatus(): bool;

    public function getMyProposalReference(): ?Amendment
    {
        if ($this->proposalReferenceId) {
            return $this->getMyConsultation()->getAmendment($this->proposalReferenceId);
        } else {
            return null;
        }
    }

    abstract public function hasAlternativeProposaltext(bool $includeOtherAmendments = false, int $internalNestingLevel = 0): bool;

    abstract public function canSeeProposedProcedure(?string $procedureToken): bool;

    /**
     * Hint: "Limited" refers to functionality that comes after setting the actual proposed procedure,
     * i.e., internal comments, voting blocks and communication with the proposer
     */
    public function canEditLimitedProposedProcedure(): bool
    {
        return User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::imotion($this)) ||
               User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
    }

    public function canEditProposedProcedure(): bool
    {
        if (!$this->canEditLimitedProposedProcedure()) {
            return false;
        }

        if ($this->isProposalPublic()) {
            return $this->getMyConsultation()->getSettings()->ppEditableAfterPublication ||
                   User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CONSULTATION_SETTINGS, null);
        } else {
            return true;
        }
    }

    public function hasVisibleAlternativeProposaltext(?string $procedureToken): bool
    {
        return ($this->hasAlternativeProposaltext(true) && (
                $this->isProposalPublic() ||
                User::havePrivilege($this->getMyConsultation(), Privileges::PRIVILEGE_CHANGE_PROPOSALS, PrivilegeQueryContext::imotion($this)) ||
                ($this->proposalFeedbackHasBeenRequested() && $this->canSeeProposedProcedure($procedureToken))
            ));
    }

    public function proposalAllowsUserFeedback(): bool
    {
        if ($this->proposalStatus === null) {
            return false;
        } else {
            return true;
        }
    }

    public function proposalFeedbackHasBeenRequested(): bool
    {
        return ($this->proposalAllowsUserFeedback() && $this->proposalNotification !== null);
    }

    public function getFormattedProposalStatus(bool $includeExplanation = false): string
    {
        if ($this->status === self::STATUS_WITHDRAWN) {
            return '<span class="withdrawn">' . \Yii::t('structure', 'STATUS_WITHDRAWN') . '</span>';
        }
        if ($this->status === self::STATUS_MOVED && is_a($this, Motion::class)) {
            /** @var Motion $this */
            return '<span class="moved">' . LayoutHelper::getMotionMovedStatusHtml($this) . '</span>';
        }
        if ($this->status === self::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION && is_a($this, Amendment::class)) {
            // @TODO backlink once we have a link from the moved amendment to the original, not just the other way round
            return \Yii::t('structure', 'STATUS_STATUS_PROPOSED_MOVE_TO_OTHER_MOTION');
        }
        $explStr = '';
        if (!$this->isProposalPublic()) {
            $explStr .= ' <span class="notVisible">' . \Yii::t('con', 'proposal_invisible') . '</span>';
        }
        if ($includeExplanation && $this->proposalExplanation) {
            $explStr .= '<blockquote class="explanation">' . \Yii::t('con', 'proposal_explanation') . ': ';
            if (str_contains($this->proposalExplanation, "\n")) {
                $explStr .= "<br>" . nl2br(Html::encode($this->proposalExplanation));
            } else {
                $explStr .= Html::encode($this->proposalExplanation);
            }
            $explStr .= '</blockquote>';
        }
        if ($this->proposalStatus === null || $this->proposalStatus == 0) {
            return $explStr;
        }

        /** @var Consultation $consultation */
        $consultation = $this->getMyConsultation();

        switch ($this->proposalStatus) {
            case self::STATUS_ACCEPTED:
                return '<span class="accepted">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_ACCEPTED)) . '</span>' . $explStr;
            case self::STATUS_REJECTED:
                return '<span class="rejected">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_REJECTED)) . '</span>' . $explStr;
            case self::STATUS_MODIFIED_ACCEPTED:
                return '<span class="modifiedAccepted">' . Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_MODIFIED_ACCEPTED)) . '</span>' . $explStr;
            case self::STATUS_REFERRED:
                return \Yii::t('amend', 'refer_to') . ': ' . Html::encode($this->proposalComment) . $explStr;
            case self::STATUS_OBSOLETED_BY_AMENDMENT:
                $refAmend = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
                if ($refAmend) {
                    $refAmendStr = Html::a($refAmend->getShortTitle(), UrlHelper::createAmendmentUrl($refAmend));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refAmendStr . $explStr;
                } else {
                    return Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_OBSOLETED_BY_AMENDMENT)) . $explStr;
                }
            case self::STATUS_OBSOLETED_BY_MOTION:
                $refMot = $this->getMyConsultation()->getMotion(intval($this->proposalComment));
                if ($refMot) {
                    $refMotStr = Html::a($refMot->getTitleWithPrefix(), UrlHelper::createMotionUrl($refMot));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refMotStr . $explStr;
                } else {
                    return Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_OBSOLETED_BY_MOTION)) . $explStr;
                }
            case self::STATUS_CUSTOM_STRING:
                return Html::encode($this->proposalComment) . $explStr;
            case self::STATUS_VOTE:
                $str = Html::encode($consultation->getStatuses()->getProposedProcedureStatusName(self::STATUS_VOTE));
                if ($this->getMyProposalReference()) {
                    $str .= ' (' . \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED') . ')';
                }
                if ($this->votingStatus === self::STATUS_ACCEPTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_ACCEPTED') . ')';
                }
                if ($this->votingStatus === self::STATUS_REJECTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_REJECTED') . ')';
                }
                $str .= $explStr;

                return $str;
            default:

                $name = Html::encode($consultation->getStatuses()->getProposedProcedureStatusName($this->proposalStatus) ?? (string) $this->proposalStatus);

                return $name . $explStr;
        }
    }

    /**
     * @throws FormError
     */
    public function setProposalVotingPropertiesFromRequest(
        ?string $votingStatus,
        ?string $votingBlockId,
        array $votingItemBlockIds,
        string $votingItemBlockName,
        string $newVotingBlockTitle,
        bool $proposedProcedureContext,
        ProposedProcedureChange $ppChanges
    ): void {
        $newVotingStatus = ($votingStatus !== null ? intval($votingStatus) : null);
        $ppChanges->setProposalVotingStatusChanges($this->votingStatus, $newVotingStatus);
        $this->votingStatus = $newVotingStatus;

        $votingBlockPre = $this->votingBlockId;
        $consultation = $this->getMyConsultation();

        /** @var VotingBlock|null $toSetVotingBlock */
        $toSetVotingBlock = null;
        if ($votingBlockId === 'NEW') {
            $newVotingBlockTitle = trim($newVotingBlockTitle);
            if ($newVotingBlockTitle !== '') {
                $toSetVotingBlock = new VotingBlock();
                $toSetVotingBlock->consultationId = $consultation->id;
                $toSetVotingBlock->position = VotingBlock::getNextAvailablePosition($consultation);
                $toSetVotingBlock->setTitle($newVotingBlockTitle);
                $toSetVotingBlock->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
                $toSetVotingBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
                $toSetVotingBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
                $toSetVotingBlock->quorumType = IQuorumType::QUORUM_TYPE_NONE;
                // If the voting is created from the proposed procedure, we assume it's only used to show it there
                $toSetVotingBlock->votingStatus = ($proposedProcedureContext ? VotingBlock::STATUS_OFFLINE : VotingBlock::STATUS_PREPARING);
                $toSetVotingBlock->save();
            }
            $consultation->refresh();
        } elseif ($votingBlockId > 0) {
            $toSetVotingBlock = $consultation->getVotingBlock(intval($votingBlockId));
        }

        if ($toSetVotingBlock) {
            if ($toSetVotingBlock->id !== $this->votingBlockId) {
                if ($this->votingBlockId && $this->votingBlock) {
                    $this->removeFromVotingBlock($this->votingBlock, false);
                }
                $this->addToVotingBlock($toSetVotingBlock, false);
            }

            $toSetId = $toSetVotingBlock->id;
            $toSetVotingBlockId = (isset($votingItemBlockIds[$toSetId]) && trim($votingItemBlockIds[$toSetId]) !== '' ? trim($votingItemBlockIds[$toSetId]) : null);
            $toSetVotingBlockName = (trim($votingItemBlockName) !== '' ? trim($votingItemBlockName) : null);

            if ($toSetVotingBlockId) {
                if (in_array($toSetVotingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
                    VotingItemGroup::setVotingItemGroupToAllItems($toSetVotingBlock, $this, $toSetVotingBlockId, $toSetVotingBlockName);
                } elseif ($toSetVotingBlockId !== $this->getVotingData()->itemGroupSameVote) {
                    throw new FormError('Cannot change an item in a running voting');
                }
            } else {
                $votingData = $this->getVotingData();
                if (in_array($toSetVotingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
                    $votingData->itemGroupSameVote = null;
                    $votingData->itemGroupName = null;
                    $this->setVotingData($votingData);
                } elseif ($votingData->itemGroupSameVote !== null) {
                    throw new FormError('Cannot change an item in a running voting');
                }
            }
        } else {
            if ($this->votingBlockId && $this->votingBlock) {
                $this->removeFromVotingBlock($this->votingBlock, false);
            }
        }

        $ppChanges->setVotingBlockChanges($votingBlockPre, $this->votingBlockId);
    }

    public function getNumOfAllVisibleComments(bool $screeningAdmin): int
    {
        return count(array_filter($this->comments, function (IComment $comment) use ($screeningAdmin) {
            return ($comment->status === IComment::STATUS_VISIBLE ||
                    ($screeningAdmin && $comment->status === IComment::STATUS_SCREENING));
        }));
    }

    /**
     * @param null|int $parentId - null == only root level comments
     *
     * @return IComment[]
     */
    public function getVisibleComments(bool $screeningAdmin, int $paragraphNo, ?int $parentId): array
    {
        $statuses = [IComment::STATUS_VISIBLE];
        if ($screeningAdmin) {
            $statuses[] = IComment::STATUS_SCREENING;
        }

        return array_filter($this->comments, function (IComment $comment) use ($statuses, $paragraphNo, $parentId) {
            if (!in_array($comment->status, $statuses)) {
                return false;
            }

            return ($paragraphNo === $comment->paragraph && $parentId === $comment->parentCommentId);
        });
    }

    abstract public function needsCollectionPhase(): bool;

    protected function iNeedsCollectionPhase(SupportBase $supportBase): bool
    {
        $needsCollectionPhase = false;
        if ($supportBase->collectSupportersBeforePublication()) {
            $supporters = $this->getSupporters(true);

            if (!$this->isInitiatedByOrganization()) {
                $minSupporters = $supportBase->getSettingsObj()->minSupporters;
                if (count($supporters) < $minSupporters) {
                    $needsCollectionPhase = true;
                }

                if ($this->getMissingSupporterCountByGender($supportBase, 'female') > 0) {
                    $needsCollectionPhase = true;
                }
            }
        }

        return $needsCollectionPhase;
    }

    public function getSupporterCountByGender(string $gender): int
    {
        $allSupporters = array_merge($this->getSupporters(true), $this->getInitiators());
        $found   = 0;
        foreach ($allSupporters as $supporter) {
            /** @var ISupporter $supporter */
            if ($supporter->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER) === $gender) {
                $found++;
            }
        }
        return $found;
    }

    public function getMissingSupporterCountByGender(SupportBase $base, string $gender): int
    {
        $minSupporters = $base->getSettingsObj()->minSupportersFemale;
        if (!$minSupporters) {
            return 0;
        }
        $found = $this->getSupporterCountByGender($gender);
        return max($minSupporters - $found, 0);
    }

    public function hasEnoughSupporters(SupportBase $supportType): bool {
        if ($this->isInitiatedByOrganization()) {
            return true;
        }

        $min           = $supportType->getSettingsObj()->minSupporters;
        $curr          = count($this->getSupporters(true));
        $missingFemale = $this->getMissingSupporterCountByGender($supportType, 'female');
        return ($curr >= $min && !$missingFemale);
    }

    /**
     * @param int[] $types
     * @return IAdminComment[]
     */
    abstract public function getAdminComments(array $types, string $sort = 'desc', ?int $limit = null): array;

    public function getProtocol(): ?IAdminComment
    {
        $protocolTypes = [IAdminComment::TYPE_PROTOCOL_PUBLIC, IAdminComment::TYPE_PROTOCOL_PRIVATE];
        $comments = $this->getAdminComments($protocolTypes);
        return (count($comments) > 0 ? $comments[0] : null);
    }

    public function setProtocol(?string $protocol, bool $public): void
    {
        $existingProtocol = $this->getProtocol();
        if ($protocol) {
            if (!$existingProtocol) {
                if (is_a($this, Motion::class)) {
                    $existingProtocol = new MotionAdminComment();
                    $existingProtocol->motionId = $this->id;
                } else {
                    $existingProtocol = new AmendmentAdminComment();
                    $existingProtocol->amendmentId = $this->id;
                }
                $existingProtocol->userId = User::getCurrentUser()->id;
                $existingProtocol->dateCreation = date('Y-m-d H:i:s');
            }

            $existingProtocol->status = ($public ? IAdminComment::TYPE_PROTOCOL_PUBLIC : IAdminComment::TYPE_PROTOCOL_PRIVATE);
            $existingProtocol->text = HTMLTools::correctHtmlErrors($protocol);
            $existingProtocol->save();
        } else {
            if ($existingProtocol) {
                $existingProtocol->delete();
            }
        }
    }

    abstract public function getUserdataExportObject(): array;

    public function getShowAlwaysToken(): string
    {
        return sha1('createToken' . AntragsgruenApp::getInstance()->randomSeed . $this->id);
    }

    private function getExtraData(): array
    {
        if ($this->extraData) {
            return json_decode($this->extraData, true);
        } else {
            return [];
        }
    }

    public function getExtraDataKey(string $key): mixed
    {
        $data = $this->getExtraData();
        return $data[$key] ?? null;
    }

    public function setExtraDataKey(string $key, $value): void
    {
        $data = $this->getExtraData();
        $data[$key] = $value;
        $this->extraData = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function isGeneralAbstention(): bool
    {
        return false;
    }
}
