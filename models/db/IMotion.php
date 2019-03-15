<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use app\models\siteSpecificBehavior\Permissions;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
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
 * @property int $votingStatus
 */
abstract class IMotion extends ActiveRecord
{
    // The motion has been deleted and is not visible anymore. Only admins can delete a motion.
    const STATUS_DELETED = -2;

    // The motion has been withdrawn, either by the user or the admin.
    const STATUS_WITHDRAWN           = -1;
    const STATUS_WITHDRAWN_INVISIBLE = -3;

    // The user has written the motion, but not yet confirmed to submit it.
    const STATUS_DRAFT = 1;

    // The user has submitted the motion, but it's not yet visible. It's up to the admin to screen it now.
    const STATUS_SUBMITTED_UNSCREENED         = 2;
    const STATUS_SUBMITTED_UNSCREENED_CHECKED = 18;

    // The default state once the motion is visible
    const STATUS_SUBMITTED_SCREENED = 3;

    // These are statuses motions and amendments get as their final state.
    // "Processed" is mostly used for amendments after merging amendments into th motion,
    // if it's unclear if it was adopted or rejected.
    // For member petitions, "Processed" means the petition has been replied.
    const STATUS_ACCEPTED          = 4;
    const STATUS_REJECTED          = 5;
    const STATUS_MODIFIED_ACCEPTED = 6;
    const STATUS_PROCESSED         = 17;

    // This is the reply to a motion / member petition and is to be shown within the parent motion view.
    const STATUS_INLINE_REPLY = 24;

    // The initiator is still collecting supporters to actually submit this motion.
    // It's visible only to those who know the link to it.
    const STATUS_COLLECTING_SUPPORTERS = 15;

    // Not yet visible, it's up to the admin to submit it
    const STATUS_DRAFT_ADMIN = 16;

    // Saved drafts while merging amendments into an motion
    const STATUS_MERGING_DRAFT_PUBLIC  = 19;
    const STATUS_MERGING_DRAFT_PRIVATE = 20;

    // The modified version of an amendment, as proposed by the admins.
    // This amendment is being referenced by proposalReference of the modified amendment.
    const STATUS_PROPOSED_MODIFIED_AMENDMENT = 21;

    // An amendment or motion has been referred to another institution.
    // The institution is documented in statusString, or, in case of a change proposal, in proposalComment
    const STATUS_REFERRED = 10;

    // An amendment becomes obsoleted by another amendment. That one is referred by an id
    // in statusString (a bit unelegantely), or, in case of a change proposal, in proposalComment
    const STATUS_OBSOLETED_BY = 22;

    // The exact status is specified in a free-text field; proposalComment if this status is used in proposalStatus
    const STATUS_CUSTOM_STRING = 23;

    // The version of a motion that the convention has agreed upon
    const STATUS_RESOLUTION_PRELIMINARY = 25;
    const STATUS_RESOLUTION_FINAL       = 26;

    // A new version of this motion exists that should be shown instead. Not visible on the home page.
    const STATUS_MODIFIED            = 7;

    // Purely informational statuses
    const STATUS_ADOPTED             = 8;
    const STATUS_COMPLETED           = 9;
    const STATUS_VOTE                = 11;
    const STATUS_PAUSED              = 12;
    const STATUS_MISSING_INFORMATION = 13;
    const STATUS_DISMISSED           = 14;

    /**
     * @param bool $includeAdminInvisibles
     * @return string[]
     */
    public static function getStatusNames($includeAdminInvisibles = false)
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
        foreach ([
                     static::STATUS_DELETED           => \Yii::t('structure', 'STATUSV_DELETED'),
                     static::STATUS_WITHDRAWN         => \Yii::t('structure', 'STATUSV_WITHDRAWN'),
                     static::STATUS_ACCEPTED          => \Yii::t('structure', 'STATUSV_ACCEPTED'),
                     static::STATUS_REJECTED          => \Yii::t('structure', 'STATUSV_REJECTED'),
                     static::STATUS_MODIFIED_ACCEPTED => \Yii::t('structure', 'STATUSV_MODIFIED_ACCEPTED'),
                     static::STATUS_MODIFIED          => \Yii::t('structure', 'STATUSV_MODIFIED'),
                     static::STATUS_ADOPTED           => \Yii::t('structure', 'STATUSV_ADOPTED'),
                     static::STATUS_REFERRED          => \Yii::t('structure', 'STATUSV_REFERRED'),
                     static::STATUS_VOTE              => \Yii::t('structure', 'STATUSV_VOTE'),
                 ] as $statusId => $statusName) {
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

    /**
     * @return bool
     */
    public function isInScreeningProcess()
    {
        return in_array($this->status, IMotion::getScreeningStatuses());
    }

    /**
     * @return bool
     */
    public function isSubmitted()
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


    /**
     * @return bool
     */
    public function isVisible()
    {
        return !in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStatuses());
    }

    /**
     * @return bool
     */
    public function isVisibleForAdmins()
    {
        return !in_array($this->status, static::getStatusesInvisibleForAdmins());
    }

    /**
     * @return bool
     */
    public function isVisibleForProposalAdmins()
    {
        return (
            $this->isVisibleForAdmins() &&
            !in_array($this->status, [
                static::STATUS_DRAFT,
                static::STATUS_DRAFT_ADMIN,
            ])
        );
    }

    /**
     * @return bool
     */
    public function isProposalPublic()
    {
        if (!$this->proposalVisibleFrom) {
            return false;
        }
        $visibleFromTs = Tools::dateSql2timestamp($this->proposalVisibleFrom);
        return ($visibleFromTs <= time());
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return !in_array($this->status, $this->getMyConsultation()->getUnreadableStatuses());
    }

    /**
     */
    abstract public function setDeleted();

    /**
     * @return bool
     */
    abstract public function isDeleted();

    /**
     * @return ISupporter[]
     */
    abstract public function getInitiators();

    /**
     * @return string
     */
    abstract public function getTitleWithPrefix();

    /**
     * @return bool
     */
    public function isInitiatedByOrganization()
    {
        foreach ($this->getInitiators() as $initiator) {
            if ($initiator->personType === ISupporter::PERSON_ORGANIZATION) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hint: the returned string is NOT yet HTML-encoded
     *
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
     * @return IMotionSection[]
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

    /**
     * @param boolean $absolute
     * @return string
     */
    abstract public function getLink($absolute = false);

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
     * @return bool
     */
    public function isSupportingPossibleAtThisStatus()
    {
        if (!($this->getLikeDislikeSettings() & SupportBase::LIKEDISLIKE_SUPPORT)) {
            return false;
        }
        if ($this->getMyMotionType()->supportType == SupportBase::COLLECTING_SUPPORTERS) {
            if ($this->status != IMotion::STATUS_COLLECTING_SUPPORTERS) {
                return false;
            }
        }
        if ($this->isDeadlineOver()) {
            return false;
        }
        return true;
    }

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
     * @return string
     */
    public function getFormattedProposalStatus($includeExplanation = false)
    {
        if ($this->status === static::STATUS_WITHDRAWN) {
            return '<span class="withdrawn">' . \Yii::t('structure', 'STATUS_WITHDRAWN') . '</span>';
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
                    if ($this->proposalReference) {
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

    /**
     * @param int[] $types
     * @param string $sort
     * @param int|null $limit
     * @return IAdminComment[]
     */
    abstract public function getAdminComments($types, $sort = 'desc', $limit = null);

    /**
     * @return array
     */
    abstract public function getUserdataExportObject();

    /**
     * @return string
     */
    public function getShowAlwaysToken()
    {
        return sha1('createToken' . AntragsgruenApp::getInstance()->randomSeed . $this->id);
    }
}
