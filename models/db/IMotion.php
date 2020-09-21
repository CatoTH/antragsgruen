<?php

namespace app\models\db;

use app\models\settings\{AntragsgruenApp, VotingData};
use app\models\siteSpecificBehavior\Permissions;
use app\components\{Tools, UrlHelper};
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;
use app\views\consultation\LayoutHelper;
use yii\base\InvalidConfigException;
use yii\db\{ActiveQueryInterface, ActiveRecord};
use yii\helpers\Html;

/**
 * Class IMotion
 * @package app\models\db
 *
 * @property string $titlePrefix
 * @property int $id
 * @property IMotionSection[] $sections
 * @property string $dateCreation
 * @property string $datePublication
 * @property string $dateResolution
 * @property IComment[] $comments
 * @property int $status
 * @property int $proposalStatus
 * @property int $proposalReferenceId
 * @property string|null $proposalVisibleFrom
 * @property string $proposalComment
 * @property string|null $proposalNotification
 * @property int $proposalUserStatus
 * @property string $proposalExplanation
 * @property string|null $votingBlockId
 * @property string|null $votingData
 * @property int $votingStatus
 * @property int|null $responsibilityId
 * @property string|null $responsibilityComment
 * @property string|null $extraData
 * @property User $responsibilityUser
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

    /**
     * @param bool $includeAdminInvisibles
     *
     * @return string[]
     */
    public static function getStatusNames(bool $includeAdminInvisibles = false)
    {
        $statuses = [
            static::STATUS_WITHDRAWN                    => \Yii::t('structure', 'STATUS_WITHDRAWN'),
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
            static::STATUS_OBSOLETED_BY                 => \Yii::t('structure', 'STATUS_OBSOLETED_BY'),
            static::STATUS_CUSTOM_STRING                => \Yii::t('structure', 'STATUS_CUSTOM_STRING'),
            static::STATUS_INLINE_REPLY                 => \Yii::t('structure', 'STATUS_INLINE_REPLY'),
            static::STATUS_RESOLUTION_PRELIMINARY       => \Yii::t('structure', 'STATUS_RESOLUTION_PRELIMINARY'),
            static::STATUS_RESOLUTION_FINAL             => \Yii::t('structure', 'STATUS_RESOLUTION_FINAL'),
            static::STATUS_MOVED                        => \Yii::t('structure', 'STATUS_MOVED'),
        ];
        if ($includeAdminInvisibles) {
            $propName = \Yii::t('structure', 'STATUS_PROPOSED_MODIFIED_AMENDMENT');

            // Keep in Sync with static::getStatusesInvisibleForAdmins
            $statuses[static::STATUS_DELETED]                     = \Yii::t('structure', 'STATUS_DELETED');
            $statuses[static::STATUS_MERGING_DRAFT_PUBLIC]        = \Yii::t('structure', 'STATUS_MERGING_DRAFT_PUBLIC');
            $statuses[static::STATUS_MERGING_DRAFT_PRIVATE]       = \Yii::t('structure', 'STATUS_MERGING_DRAFT_PRIVATE');
            $statuses[static::STATUS_PROPOSED_MODIFIED_AMENDMENT] = $propName;
        }

        return $statuses;
    }

    /**
     * @return string[]
     */
    public static function getStatusesAsVerbs()
    {
        $return = static::getStatusNames();
        foreach (
            [
                static::STATUS_DELETED           => \Yii::t('structure', 'STATUSV_DELETED'),
                static::STATUS_WITHDRAWN         => \Yii::t('structure', 'STATUSV_WITHDRAWN'),
                static::STATUS_ACCEPTED          => \Yii::t('structure', 'STATUSV_ACCEPTED'),
                static::STATUS_REJECTED          => \Yii::t('structure', 'STATUSV_REJECTED'),
                static::STATUS_MODIFIED_ACCEPTED => \Yii::t('structure', 'STATUSV_MODIFIED_ACCEPTED'),
                static::STATUS_MODIFIED          => \Yii::t('structure', 'STATUSV_MODIFIED'),
                static::STATUS_ADOPTED           => \Yii::t('structure', 'STATUSV_ADOPTED'),
                static::STATUS_REFERRED          => \Yii::t('structure', 'STATUSV_REFERRED'),
                static::STATUS_VOTE              => \Yii::t('structure', 'STATUSV_VOTE'),
            ] as $statusId => $statusName
        ) {
            $return[$statusId] = $statusName;
        }

        return $return;
    }

    /**
     * @return string[]
     */
    public static function getVotingStatuses()
    {
        return [
            static::STATUS_VOTE     => \Yii::t('structure', 'STATUS_VOTE'),
            static::STATUS_ACCEPTED => \Yii::t('structure', 'STATUS_ACCEPTED'),
            static::STATUS_REJECTED => \Yii::t('structure', 'STATUS_REJECTED'),
        ];
    }

    /**
     * @return int[]
     */
    public static function getScreeningStatuses()
    {
        return [
            static::STATUS_SUBMITTED_UNSCREENED,
            static::STATUS_SUBMITTED_UNSCREENED_CHECKED
        ];
    }

    public function isInScreeningProcess(): bool
    {
        return in_array($this->status, IMotion::getScreeningStatuses());
    }

    public function isSubmitted(): bool
    {
        return !in_array($this->status, [
            IMotion::STATUS_DELETED,
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
    public static function getStatusesMarkAsDoneOnRewriting()
    {
        return [
            static::STATUS_PROCESSED,
            static::STATUS_ACCEPTED,
            static::STATUS_REJECTED,
            static::STATUS_MODIFIED_ACCEPTED,
        ];
    }

    /**
     * @return int[]
     */
    public static function getStatusesInvisibleForAdmins()
    {
        // Keep in sync with getStatusNames::$includeAdminInvisibles
        return [
            static::STATUS_DELETED,
            static::STATUS_MERGING_DRAFT_PUBLIC,
            static::STATUS_MERGING_DRAFT_PRIVATE,
            static::STATUS_PROPOSED_MODIFIED_AMENDMENT,
        ];
    }

    /**
     * @return string[]
     */
    public static function getStatusNamesVisibleForAdmins()
    {
        $names     = [];
        $invisible = static::getStatusesInvisibleForAdmins();
        foreach (static::getStatusNames() as $id => $name) {
            if (!in_array($id, $invisible)) {
                $names[$id] = $name;
            }
        }

        return $names;
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
        if (!is_object($this->votingDataObject)) {
            $this->votingDataObject = new VotingData($this->votingData);
        }

        return $this->votingDataObject;
    }

    public function setVotingData(VotingData $data): void
    {
        $this->votingDataObject = $data;
        $this->votingData       = json_encode($data, JSON_PRETTY_PRINT);
    }


    public function isVisible(): bool
    {
        if (!$this->getMyConsultation()) {
            return false;
        }

        return !in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses());
    }

    public function isVisibleForAdmins(): bool
    {
        return !in_array($this->status, static::getStatusesInvisibleForAdmins());
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

        return !in_array($this->status, $this->getMyConsultation()->getUnreadableStatuses());
    }

    abstract public function setDeleted(): void;

    abstract public function isDeleted(): bool;

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators();

    /**
     * @return string
     */
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

    public function getInitiatorsStr(): string
    {
        $cached = $this->getCacheItem('supporters.initiatorStr');
        if ($cached !== null) {
            return $cached;
        }

        $inits = $this->getInitiators();
        $str   = [];
        foreach ($inits as $init) {
            $str[] = $init->getNameWithResolutionDate(false);
        }

        $initiatorsStr = implode(', ', $str);
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
     * @param bool $withoutTitle
     *
     * @return MotionSection[]
     */
    public function getSortedSections($withoutTitle = false)
    {
        $sectionsIn = [];
        $title      = $this->getTitleSection();
        foreach ($this->getActiveSections() as $section) {
            if (!$withoutTitle || $section !== $title) {
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

    abstract public function getLink(bool $absolute = false): string;

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTime()
    {
        if ($this->dateCreation) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateCreation);
        } else {
            return null;
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getPublicationDateTime()
    {
        if ($this->datePublication) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->datePublication);
        } else {
            return null;
        }
    }

    abstract public function isSupportingPossibleAtThisStatus(): bool;

    /**
     * @return bool
     */
    public function proposalAllowsUserFeedback()
    {
        if ($this->proposalStatus === null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return bool
     */
    public function proposalFeedbackHasBeenRequested()
    {
        return ($this->proposalAllowsUserFeedback() && $this->proposalNotification !== null);
    }

    /**
     * @param bool $includeExplanation
     *
     * @return string
     */
    public function getFormattedProposalStatus($includeExplanation = false)
    {
        if ($this->status === static::STATUS_WITHDRAWN) {
            return '<span class="withdrawn">' . \Yii::t('structure', 'STATUS_WITHDRAWN') . '</span>';
        }
        if ($this->status === static::STATUS_MOVED && is_a($this, Motion::class)) {
            /** @var Motion $this */
            return '<span class="moved">' . LayoutHelper::getMotionMovedStatusHtml($this) . '</span>';
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
                $refAmend = $this->getMyConsultation()->getAmendment($this->proposalComment);
                if ($refAmend) {
                    $refAmendStr = Html::a($refAmend->getShortTitle(), UrlHelper::createAmendmentUrl($refAmend));

                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refAmendStr . $explStr;
                } else {
                    return static::getProposedStatusNames()[$this->proposalStatus] . $explStr;
                }
                break;
            case static::STATUS_CUSTOM_STRING:
                return Html::encode($this->proposalComment) . $explStr;
                break;
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
                break;
            default:
                if (isset(static::getProposedStatusNames()[$this->proposalStatus])) {
                    return static::getProposedStatusNames()[$this->proposalStatus] . $explStr;
                } else {
                    return $this->proposalStatus . '?' . $explStr;
                }
        }
    }

    /**
     * @param $titlePrefix
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

    /**
     * @param bool $screeningAdmin
     *
     * @return int
     */
    public function getNumOfAllVisibleComments($screeningAdmin)
    {
        return count(array_filter($this->comments, function (IComment $comment) use ($screeningAdmin) {
            return ($comment->status === IComment::STATUS_VISIBLE ||
                    ($screeningAdmin && $comment->status === IComment::STATUS_SCREENING));
        }));
    }

    /**
     * @param bool $screeningAdmin
     * @param int $paragraphNo
     * @param null|int $parentId - null == only root level comments
     *
     * @return IComment[]
     */
    public function getVisibleComments($screeningAdmin, $paragraphNo, $parentId)
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
            $supporters = $this->getSupporters();
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
        $allSupporters = array_merge($this->getSupporters(), $this->getInitiators());
        $found   = 0;
        foreach ($allSupporters as $supporter) {
            /** @var ISupporter $supporter */
            if ($supporter->getExtraDataEntry('gender') === $gender) {
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
        return (isset($data[$key]) ? $data[$key] : null);
    }

    public function setExtraDataKey(string $key, $value): void
    {
        $data = $this->getExtraData();
        $data[$key] = $value;
        $this->extraData = json_encode($data);
    }
}
