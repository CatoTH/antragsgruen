<?php

namespace app\models\db;

use app\models\settings\{AntragsgruenApp, VotingData};
use app\models\consultationLog\ProposedProcedureChange;
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use app\models\siteSpecificBehavior\Permissions;
use app\models\VotingItemGroup;
use app\components\{Tools, UrlHelper};
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;
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
 * @property IComment[] $comments
 * @property ConsultationSettingsTag[] $tags
 * @property ConsultationAgendaItem|null $agendaItem
 * @property int $status
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
abstract class IMotion extends ActiveRecord
{
    use CacheTrait;

    // The motion has been deleted and is not visible anymore. Only admins can delete a motion.
    const STATUS_DELETED = -2;

    // The motion has been withdrawn, either by the user or the admin.
    const STATUS_WITHDRAWN = -1;
    const STATUS_WITHDRAWN_INVISIBLE = -3;

    // The user has written the motion, but not yet confirmed to submit it.
    const STATUS_DRAFT = 1;

    // The user has submitted the motion, but it's not yet visible. It's up to the admin to screen it now.
    const STATUS_SUBMITTED_UNSCREENED = 2;
    const STATUS_SUBMITTED_UNSCREENED_CHECKED = 18;

    // The default state once the motion is visible
    const STATUS_SUBMITTED_SCREENED = 3;

    // These are statuses motions and amendments get as their final state.
    // "Processed" is mostly used for amendments after merging amendments into th motion,
    // if it's unclear if it was adopted or rejected.
    // For member petitions, "Processed" means the petition has been replied.
    const STATUS_ACCEPTED = 4;
    const STATUS_REJECTED = 5;
    const STATUS_MODIFIED_ACCEPTED = 6;
    const STATUS_PROCESSED = 17;

    // This is the reply to a motion / member petition and is to be shown within the parent motion view.
    const STATUS_INLINE_REPLY = 24;

    // The initiator is still collecting supporters to actually submit this motion.
    // It's visible only to those who know the link to it.
    const STATUS_COLLECTING_SUPPORTERS = 15;

    // Not yet visible, it's up to the admin to submit it
    const STATUS_DRAFT_ADMIN = 16;

    // Saved drafts while merging amendments into an motion
    const STATUS_MERGING_DRAFT_PUBLIC = 19;
    const STATUS_MERGING_DRAFT_PRIVATE = 20;

    // The modified version of an amendment, as proposed by the admins.
    // This amendment is being referenced by proposalReference of the modified amendment.
    const STATUS_PROPOSED_MODIFIED_AMENDMENT = 21;

    // Used as a status for amendment, which is the proposed move of an amendment to another motion.
    // The original amendment gets this status as `proposalStatus`, the internal new amendment (for the other motion) gets this status as `status`.
    // The internal new amendment should not be used-visible in the context of its motion (only when merging),
    // only within the amendment that references this one via its proposalReference
    const STATUS_PROPOSED_MOVE_TO_OTHER_MOTION = 28;

    // An amendment or motion has been referred to another institution.
    // The institution is documented in statusString, or, in case of a change proposal, in proposalComment
    const STATUS_REFERRED = 10;

    // The motion still exists at the original place, but has been replaced by a copy at another consultation or agenda item.
    // This motion is referenced by the new motion as parentMotionId.
    // Amendments cannot be moved, they are always sticked to the motion.
    const STATUS_MOVED = 27;

    // An amendment becomes obsoleted by another amendment. That one is referred by an id
    // in statusString (a bit unelegantely), or, in case of a change proposal, in proposalComment
    const STATUS_OBSOLETED_BY = 22;

    // The exact status is specified in a free-text field; proposalComment if this status is used in proposalStatus
    const STATUS_CUSTOM_STRING = 23;

    // The version of a motion that the convention has agreed upon
    const STATUS_RESOLUTION_PRELIMINARY = 25;
    const STATUS_RESOLUTION_FINAL = 26;

    // A new version of this motion exists that should be shown instead. Not visible on the home page.
    const STATUS_MODIFIED = 7;

    // Purely informational statuses
    const STATUS_ADOPTED = 8;
    const STATUS_COMPLETED = 9;
    const STATUS_VOTE = 11;
    const STATUS_PAUSED = 12;
    const STATUS_MISSING_INFORMATION = 13;
    const STATUS_DISMISSED = 14;

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
        $query->andWhere('status != ' . static::STATUS_DELETED);

        return $query;
    }

    /**
     * @return Permissions
     */
    public function getPermissionsObject()
    {
        $behavior  = $this->getMyConsultation()->site->getBehaviorClass();
        $className = $behavior->getPermissionsClass();

        return new $className();
    }

    /** @var null|VotingData */
    private $votingDataObject = null;

    public function getVotingData(): VotingData
    {
        $className = VotingData::class;
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            if ($plugin::getVotingDataClass($this->getMyConsultation()) !== null) {
                $className = $plugin::getVotingDataClass($this->getMyConsultation());
            }
        }

        if (!is_object($this->votingDataObject)) {
            $this->votingDataObject = new $className($this->votingData);
        }

        return $this->votingDataObject;
    }

    public function setVotingData(VotingData $data): void
    {
        $this->votingDataObject = $data;
        $this->votingData       = json_encode($data, JSON_PRETTY_PRINT);
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

    public function isVisible(): bool
    {
        if (!$this->getMyConsultation()) {
            return false;
        }

        return !in_array($this->status, $this->getMyConsultation()->getStatuses()->getInvisibleMotionStatuses());
    }

    public function isVisibleForAdmins(): bool
    {
        return !in_array($this->status, $this->getMyConsultation()->getStatuses()->getStatusesInvisibleForAdmins());
    }

    public function isVisibleForProposalAdmins(): bool
    {
        return (
            $this->isVisibleForAdmins() &&
            !in_array($this->status, [
                static::STATUS_DRAFT,
                static::STATUS_DRAFT_ADMIN,
            ])
        );
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
        $iAmAdmin = User::havePrivilege($this->getMyConsultation(), User::PRIVILEGE_CONTENT_EDIT);
        if ($iAmAdmin && in_array($this->status, [static::STATUS_DRAFT, static::STATUS_DRAFT_ADMIN])) {
            return true;
        }

        return !in_array($this->status, $this->getMyConsultation()->getStatuses()->getUnreadableStatuses());
    }

    abstract public function setDeleted(): void;

    abstract public function isDeleted(): bool;

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators(): array;

    abstract public function iAmInitiator(): bool;

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
    abstract public function getTypeSections();

    /**
     * @return IMotionSection[]
     */
    abstract public function getActiveSections();

    /**
     * @return string[]
     */
    public static function getProposedStatusNames()
    {
        return [
            static::STATUS_ACCEPTED          => \Yii::t('structure', 'PROPOSED_ACCEPTED_AMEND'),
            static::STATUS_REJECTED          => \Yii::t('structure', 'PROPOSED_REJECTED'),
            static::STATUS_MODIFIED_ACCEPTED => \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED'),
            static::STATUS_REFERRED          => \Yii::t('structure', 'PROPOSED_REFERRED'),
            static::STATUS_VOTE              => \Yii::t('structure', 'PROPOSED_VOTE'),
            static::STATUS_OBSOLETED_BY      => \Yii::t('structure', 'PROPOSED_OBSOLETED_BY_AMEND'),
            static::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION => \Yii::t('structure', 'PROPOSED_MOVE_TO_OTHER_MOTION'),
            static::STATUS_CUSTOM_STRING     => \Yii::t('structure', 'PROPOSED_CUSTOM_STRING'),
        ];
    }

    /**
     * @return IMotionSection|null
     */
    public function getTitleSection()
    {
        foreach ($this->sections as $section) {
            if ($section->getSettings() && $section->getSettings()->type === ISectionType::TYPE_TITLE) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @return MotionSection[]|AmendmentSection[]
     */
    public function getSortedSections(bool $withoutTitle = false): array
    {
        $sectionsIn = [];
        $title      = $this->getTitleSection();
        foreach ($this->getActiveSections() as $section) {
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

    /**
     * @return ConsultationMotionType
     */
    abstract public function getMyMotionType();

    /**
     * @return int
     */
    abstract public function getLikeDislikeSettings();

    abstract public function isDeadlineOver(): bool;

    abstract public function getLink(bool $absolute = false): string;

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    public function getDateTime(): ?\DateTime
    {
        if ($this->dateCreation) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateCreation);
        } else {
            return null;
        }
    }

    public function getPublicationDateTime(): ?\DateTime
    {
        if ($this->datePublication) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->datePublication);
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

    abstract public function isSupportingPossibleAtThisStatus(): bool;

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
        if ($this->status === static::STATUS_WITHDRAWN) {
            return '<span class="withdrawn">' . \Yii::t('structure', 'STATUS_WITHDRAWN') . '</span>';
        }
        if ($this->status === static::STATUS_MOVED && is_a($this, Motion::class)) {
            /** @var Motion $this */
            return '<span class="moved">' . LayoutHelper::getMotionMovedStatusHtml($this) . '</span>';
        }
        if ($this->status === static::STATUS_PROPOSED_MOVE_TO_OTHER_MOTION && is_a($this, Amendment::class)) {
            // @TODO backlink once we have a link from the moved amendment to the original, not just the other way round
            return \Yii::t('structure', 'STATUS_STATUS_PROPOSED_MOVE_TO_OTHER_MOTION');
        }
        $explStr = '';
        if ($includeExplanation && $this->proposalExplanation) {
            $explStr .= ' <span class="explanation">(' . \Yii::t('con', 'proposal_explanation') . ': ';
            $explStr .= Html::encode($this->proposalExplanation);
            $explStr .= ')</span>';
        }
        if ($includeExplanation && !$this->isProposalPublic()) {
            $explStr .= ' <span class="notVisible">' . \Yii::t('con', 'proposal_invisible') . '</span>';
        }
        if ($this->proposalStatus === null || $this->proposalStatus == 0) {
            return $explStr;
        }
        switch ($this->proposalStatus) {
            case static::STATUS_REFERRED:
                return \Yii::t('amend', 'refer_to') . ': ' . Html::encode($this->proposalComment) . $explStr;
            case static::STATUS_OBSOLETED_BY:
                $refAmend = $this->getMyConsultation()->getAmendment(intval($this->proposalComment));
                if ($refAmend) {
                    $refAmendStr = Html::a($refAmend->getShortTitle(), UrlHelper::createAmendmentUrl($refAmend));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refAmendStr . $explStr;
                } else {
                    return static::getProposedStatusNames()[$this->proposalStatus] . $explStr;
                }
            case static::STATUS_CUSTOM_STRING:
                return Html::encode($this->proposalComment) . $explStr;
            case static::STATUS_VOTE:
                $str = static::getProposedStatusNames()[$this->proposalStatus];
                if (is_a($this, Amendment::class)) {
                    /** @var Amendment $this */
                    if ($this->getMyProposalReference()) {
                        $str .= ' (' . \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED') . ')';
                    }
                }
                if ($this->votingStatus === static::STATUS_ACCEPTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_ACCEPTED') . ')';
                }
                if ($this->votingStatus === static::STATUS_REJECTED) {
                    $str .= ' (' . \Yii::t('structure', 'STATUS_REJECTED') . ')';
                }
                $str .= $explStr;

                return $str;
            default:
                if (isset(static::getProposedStatusNames()[$this->proposalStatus])) {
                    return static::getProposedStatusNames()[$this->proposalStatus] . $explStr;
                } else {
                    return $this->proposalStatus . '?' . $explStr;
                }
        }
    }

    /**
     * @throws FormError
     */
    public function addToVotingBlock(VotingBlock $votingBlock, bool $save): void
    {
        if (!$votingBlock->itemsCanBeAdded()) {
            throw new FormError('Cannot add an item to a running voting');
        }

        $this->votingBlockId = $votingBlock->id;

        foreach ($votingBlock->votes as $vote) {
            if ($vote->isForIMotion($this)) {
                $vote->delete();
            }
        }

        if ($save) {
            $this->save();
        }
    }

    /**
     * @throws FormError
     */
    public function removeFromVotingBlock(VotingBlock $votingBlock, bool $save): void
    {
        if (!$votingBlock->itemsCanBeRemoved()) {
            throw new FormError('Cannot remove an item from a running voting');
        }

        $this->votingBlockId = null;

        $votingData = $this->getVotingData();
        $votingData->itemGroupSameVote = null;
        $this->setVotingData($votingData);

        if ($save) {
            $this->save();
        }
    }

    /**
     * @throws FormError
     */
    public function setProposalVotingPropertiesFromRequest(
        ?string $votingStatus,
        ?string $votingBlockId,
        array $votingItemBlockIds,
        string $newVotingBlockTitle,
        bool $proposedProcedureContext,
        ProposedProcedureChange $ppChanges
    ): void {
        $newVotingStatus = ($votingStatus !== null ? intval($votingStatus) : null);
        $ppChanges->setProposalVotingStatusChanges($this->votingStatus, $newVotingStatus);
        $this->votingStatus = $newVotingStatus;

        $votingBlockPre = $this->votingBlockId;

        /** @var VotingBlock|null $toSetVotingBlock */
        $toSetVotingBlock = null;
        if ($votingBlockId === 'NEW') {
            $newVotingBlockTitle = trim($newVotingBlockTitle);
            if ($newVotingBlockTitle !== '') {
                $toSetVotingBlock = new VotingBlock();
                $toSetVotingBlock->consultationId = $this->getMyConsultation()->id;
                $toSetVotingBlock->title = $newVotingBlockTitle;
                $toSetVotingBlock->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
                $toSetVotingBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
                $toSetVotingBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
                // If the voting is created from the proposed procedure, we assume it's only used to show it there
                $toSetVotingBlock->votingStatus = ($proposedProcedureContext ? VotingBlock::STATUS_OFFLINE : VotingBlock::STATUS_PREPARING);
                $toSetVotingBlock->save();
            }
        } elseif ($votingBlockId > 0) {
            $toSetVotingBlock = $this->getMyConsultation()->getVotingBlock($votingBlockId);
        }

        if ($toSetVotingBlock) {
            if ($toSetVotingBlock->id !== $this->votingBlockId) {
                if ($this->votingBlockId && $this->votingBlock) {
                    $this->removeFromVotingBlock($this->votingBlock, false);
                }
                $this->addToVotingBlock($toSetVotingBlock, false);
            }

            if (isset($votingItemBlockIds[$toSetVotingBlock->id]) && trim($votingItemBlockIds[$toSetVotingBlock->id]) !== '') {
                if (in_array($toSetVotingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
                    VotingItemGroup::setVotingItemGroupToAllItems($this, $votingItemBlockIds[$toSetVotingBlock->id]);
                } elseif ($votingItemBlockIds[$toSetVotingBlock->id] !== $this->getVotingData()->itemGroupSameVote) {
                    throw new FormError('Cannot change an item in a running voting');
                }
            } else {
                $votingData = $this->getVotingData();
                if (in_array($toSetVotingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
                    $votingData->itemGroupSameVote = null;
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

    /**
     * @param string $titlePrefix
     *
     * @return string
     */
    public static function getNewTitlePrefixInternal($titlePrefix)
    {
        $new      = \Yii::t('motion', 'prefix_new_code');
        $newMatch = preg_quote($new, '/');
        if (preg_match('/' . $newMatch . '/i', $titlePrefix)) {
            $parts = preg_split('/(' . $newMatch . '\s*)/i', $titlePrefix, -1, PREG_SPLIT_DELIM_CAPTURE);
            $last  = array_pop($parts);
            $last  = ($last > 0 ? $last + 1 : 2); // NEW BLA -> NEW 2
            array_push($parts, $last);

            return implode("", $parts);
        } else {
            return $titlePrefix . $new;
        }
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
            $initiators = $this->getInitiators();

            $isOrganization = false;
            foreach ($initiators as $initiator) {
                if ($initiator->personType == ISupporter::PERSON_ORGANIZATION) {
                    $isOrganization = true;
                }
            }
            if (!$isOrganization) {
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
        $min           = $supportType->getSettingsObj()->minSupporters;
        $curr          = count($this->getSupporters(true));
        $missingFemale = $this->getMissingSupporterCountByGender($supportType, 'female');
        return ($curr >= $min && !$missingFemale);
    }

    /**
     * @param int[] $types
     * @param string $sort
     * @param int|null $limit
     *
     * @return IAdminComment[]
     */
    abstract public function getAdminComments($types, $sort = 'desc', $limit = null);

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

    public function getExtraDataKey(string $key)
    {
        $data = $this->getExtraData();
        return $data[$key] ?? null;
    }

    public function setExtraDataKey(string $key, $value): void
    {
        $data = $this->getExtraData();
        $data[$key] = $value;
        $this->extraData = json_encode($data);
    }
}
