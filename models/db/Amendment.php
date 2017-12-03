<?php

namespace app\models\db;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DiffRenderer;
use app\components\HashedStaticCache;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\FormError;
use app\models\notifications\AmendmentPublished as AmendmentPublishedNotification;
use app\models\notifications\AmendmentSubmitted as AmendmentSubmittedNotification;
use app\models\notifications\AmendmentWithdrawn as AmendmentWithdrawnNotification;
use app\models\policies\All;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
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
 * @property string $dateCreation
 * @property string $datePublication
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 * @property int $globalAlternative
 * @property int $proposalStatus
 * @property int $proposalReferenceId
 * @property string|null $proposalVisibleFrom
 * @property string $proposalComment
 * @property string|null $proposalNotification
 * @property int $proposalUserStatus
 * @property string $proposalExplanation
 * @property string|null $votingBlockId
 * @property int $votingStatus
 *
 * @property AmendmentComment[] $comments
 * @property AmendmentAdminComment[] $adminComments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 * @property Amendment $proposalReference
 * @property Amendment $proposalReferencedBy
 * @property VotingBlock $votingBlock
 */
class Amendment extends IMotion implements IRSSItem
{
    use CacheTrait;

    /**
     * @return string[]
     */
    public static function getProposedChangeStati()
    {
        $stati = [
            IMotion::STATUS_ACCEPTED,
            IMotion::STATUS_REJECTED,
            IMotion::STATUS_MODIFIED_ACCEPTED,
            IMotion::STATUS_REFERRED,
            IMotion::STATUS_VOTE,
            IMotion::STATUS_OBSOLETED_BY,
            IMotion::STATUS_CUSTOM_STRING,
        ];
        if (Consultation::getCurrent()) {
            $stati = Consultation::getCurrent()->site->getBehaviorClass()->getProposedChangeStati($stati);
        }
        return $stati;
    }

    /**
     * @return string[]
     */
    public static function getProposedStatiNames()
    {
        return [
            static::STATUS_ACCEPTED          => \Yii::t('structure', 'PROPOSED_ACCEPTED'),
            static::STATUS_REJECTED          => \Yii::t('structure', 'PROPOSED_REJECTED'),
            static::STATUS_MODIFIED_ACCEPTED => \Yii::t('structure', 'PROPOSED_MODIFIED_ACCEPTED'),
            static::STATUS_REFERRED          => \Yii::t('structure', 'PROPOSED_REFERRED'),
            static::STATUS_VOTE              => \Yii::t('structure', 'PROPOSED_VOTE'),
            static::STATUS_OBSOLETED_BY      => \Yii::t('structure', 'PROPOSED_OBSOLETED_BY'),
            static::STATUS_CUSTOM_STRING     => \Yii::t('structure', 'PROPOSED_CUSTOM_STRING'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getProposalStatiAsVerbs()
    {
        $return = static::getProposedStatiNames();
        foreach ([
                     static::STATUS_ACCEPTED          => \Yii::t('structure', 'PROPOSEDV_ACCEPTED'),
                     static::STATUS_REJECTED          => \Yii::t('structure', 'PROPOSEDV_REJECTED'),
                     static::STATUS_MODIFIED_ACCEPTED => \Yii::t('structure', 'PROPOSEDV_MODIFIED_ACCEPTED'),
                     static::STATUS_REFERRED          => \Yii::t('structure', 'PROPOSEDV_REFERRED'),
                     static::STATUS_VOTE              => \Yii::t('structure', 'PROPOSEDV_VOTE'),
                 ] as $statusId => $statusName) {
            $return[$statusId] = $statusName;
        }
        return $return;
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
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdminComments()
    {
        return $this->hasMany(AmendmentAdminComment::class, ['amendmentId' => 'id'])
            ->andWhere(AmendmentAdminComment::tableName() . '.status != ' . AmendmentAdminComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::class, ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
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

    /**
     * @return ActiveQuery
     */
    public function getProposalReference()
    {
        return $this->hasOne(Amendment::class, ['id' => 'proposalReferenceId']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProposalReferencedBy()
    {
        return $this->hasOne(Amendment::class, ['proposalReferenceId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId']);
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


    /**
     * @return string
     */
    public function getTitle()
    {
        $motion = $this->getMyMotion();
        if ($motion->titlePrefix != '') {
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

    /**
     * @return string
     */
    public function getShortTitle()
    {
        if ($this->getMyMotion()->titlePrefix != '') {
            $showMotionPrefix = (mb_stripos($this->titlePrefix, $this->getMyMotion()->titlePrefix) === false);
        } else {
            $showMotionPrefix = false;
        }
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . \Yii::t('amend', 'amend_for') . $this->getMyMotion()->title;
        } else {
            if ($this->getMyMotion()->titlePrefix != '') {
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

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getAmendment($this->id)) {
            return $current;
        } else {
            /** @var Motion $motion */
            $motion = Motion::findOne($this->motionId);
            return Consultation::findOne($motion->consultationId);
        }
    }

    private $myMotion = null;

    /**
     * @return Motion
     */
    public function getMyMotion()
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
     * @return \yii\db\ActiveQuery
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
        return $this->getMyMotion()->motionType->motionSections;
    }

    /**
     * @param string $changeId
     * @return array
     */
    public function getInlineChangeData($changeId)
    {
        if ($this->status == Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
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
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

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
     */
    public function getFirstDiffLine()
    {
        $cached = $this->getCacheItem('getFirstDiffLine');
        if ($cached !== null) {
            return $cached;
        }
        $firstLine  = $this->getMyMotion()->getFirstLineNumber();
        $lineLength = $this->getMyConsultation()->getSettings()->lineLength;
        $original   = $new = [];

        foreach ($this->getActiveSections() as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $original[] = $section->getOriginalMotionSection()->data;
            $new[]      = $section->data;
        }

        $firstLine = static::calcFirstDiffLineCached($firstLine, $lineLength, $original, $new);

        $this->setCacheItem('getFirstDiffLine', $firstLine);
        return $firstLine;
    }

    /**
     * @param Amendment $ae1
     * @param Amendment $ae2
     * @return int
     */
    public static function sortVisibleByLineNumbersSort($ae1, $ae2)
    {
        $first1 = $ae1->getFirstDiffLine();
        $first2 = $ae2->getFirstDiffLine();

        if ($first1 < $first2) {
            return -1;
        }
        if ($first1 > $first2) {
            return 1;
        }

        $tit1 = explode('-', $ae1->titlePrefix);
        $tit2 = explode('-', $ae2->titlePrefix);
        if (count($tit1) > 2 && count($tit1) == count($tit2)) {
            if ($tit1[count($tit1) - 1] < $tit2[count($tit2) - 1]) {
                return -1;
            }
            if ($tit1[count($tit1) - 1] > $tit2[count($tit2) - 1]) {
                return 1;
            }
            return 0;
        } else {
            return strcasecmp($ae1->titlePrefix, $ae2->titlePrefix);
        }
    }


    /**
     * @param Consultation $consultation
     * @param Amendment[] $amendments
     * @return Amendment[]
     */
    public static function sortVisibleByLineNumbers(Consultation $consultation, $amendments)
    {
        $ams = [];
        foreach ($amendments as $am) {
            if (!in_array($am->status, $consultation->getInvisibleAmendmentStati())) {
                $am->getFirstDiffLine();
                $ams[] = $am;
            }
        }

        usort($ams, [Amendment::class, 'sortVisibleByLineNumbersSort']);

        return $ams;
    }

    /**
     * @param Consultation $consultation
     * @param int $limit
     * @return Amendment[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
        $query          = Amendment::find();
        $query->where('amendment.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($invisibleStati, $consultation) {
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
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
        $query->where('amendment.status IN (' . implode(', ', static::getScreeningStati()) . ')');
        $query->joinWith(
            [
                'motionJoin' => function ($query) use ($consultation) {
                    $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());
                    /** @var ActiveQuery $query */
                    $query->andWhere('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
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
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getSupporters()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_SUPPORTER) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getLikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_LIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     * @return AmendmentSupporter[]
     */
    public function getDislikes()
    {
        $return = [];
        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        };
        return $return;
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

        foreach ($this->amendmentSupporters as $supp) {
            if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
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
        if ($this->status == static::STATUS_DRAFT) {
            $hadLoggedInUser = false;
            foreach ($this->amendmentSupporters as $supp) {
                $currUser = User::getCurrentUser();
                if ($supp->role == AmendmentSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    if ($currUser && $currUser->id == $supp->userId) {
                        return true;
                    }
                }
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId === null) {
                    if ($currUser && $currUser->hasPrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
                        return true;
                    }
                }
            }
            if ($hadLoggedInUser) {
                return false;
            } else {
                if ($this->getMyMotion()->motionType->getAmendmentPolicy()->getPolicyID() == All::getPolicyID()) {
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
            if ($this->getMyMotion()->motionType->amendmentDeadlineIsOver()) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canWithdraw()
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

    /**
     * @return bool
     */
    public function canFinishSupportCollection()
    {
        if (!$this->iAmInitiator()) {
            return false;
        }
        if ($this->status != Amendment::STATUS_COLLECTING_SUPPORTERS) {
            return false;
        }
        if ($this->isDeadlineOver()) {
            return false;
        }
        $supporters    = count($this->getSupporters());
        $minSupporters = $this->getMyMotion()->motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
        return ($supporters >= $minSupporters);
    }

    /**
     * @param bool $ignoreCollissionProblems
     * @return bool
     */
    public function canMergeIntoMotion($ignoreCollissionProblems = false)
    {
        if ($this->getMyConsultation()->havePrivilege(User::PRIVILEGE_CONTENT_EDIT)) {
            return true;
        } elseif ($this->getMyMotion()->iAmInitiator()) {
            $policy = $this->getMyMotionType()->initiatorsCanMergeAmendments;
            if ($policy == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION) {
                return true;
            } elseif ($policy == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION && $ignoreCollissionProblems) {
                return true;
            } elseif ($policy == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION && !$ignoreCollissionProblems) {
                return (count($this->getCollidingAmendments()) == 0);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isSocialSharable()
    {
        if ($this->getMyConsultation()->site->getSettings()->forceLogin) {
            return false;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati(true))) {
            return false;
        }
        return true;
    }

    /** @var null|MotionSectionParagraphAmendment[] */
    private $changedParagraphCache = null;

    /**
     * @param MotionSection[] $motionSections
     * @param bool $lineNumbers
     * @return MotionSectionParagraphAmendment[]
     */
    public function getChangedParagraphs($motionSections, $lineNumbers)
    {
        if ($lineNumbers && $this->changedParagraphCache !== null) {
            return $this->changedParagraphCache;
        }
        $paragraphs = [];
        foreach ($motionSections as $section) {
            if ($section->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $paras = $section->getTextParagraphObjects($lineNumbers, true, true);
            foreach ($paras as $para) {
                foreach ($para->amendmentSections as $amSec) {
                    if ($amSec->amendmentId == $this->id) {
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
     */
    public function getCollidingAmendments()
    {
        $mySections = [];
        foreach ($this->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $mySections[$section->sectionId] = $section->data;
        }

        $colliding = [];
        foreach ($this->getMyMotion()->getAmendmentsRelevantForCollissionDetection([$this]) as $amend) {
            foreach ($amend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollissions($mySections[$section->sectionId], false, false);
                if (count($coll) > 0) {
                    $colliding[$amend->id] = $amend;
                }
            }
        }

        return $colliding;
    }

    /**
     */
    public function withdraw()
    {
        if ($this->status == Amendment::STATUS_DRAFT) {
            $this->status = static::STATUS_DELETED;
        } elseif (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati())) {
            $this->status = static::STATUS_WITHDRAWN_INVISIBLE;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->getMyMotion()->flushCacheStart();

        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_WITHDRAW, $this->id);
        new AmendmentWithdrawnNotification($this);
    }

    /**
     * @return bool
     */
    public function needsCollectionPhase()
    {
        $needsCollectionPhase = false;
        $motionType           = $this->getMyMotion()->motionType;
        if ($motionType->getAmendmentSupportTypeClass()->collectSupportersBeforePublication()) {
            $isOrganization = false;
            foreach ($this->getInitiators() as $initiator) {
                if ($initiator->personType == ISupporter::PERSON_ORGANIZATION) {
                    $isOrganization = true;
                }
            }
            $supporters    = count($this->getSupporters());
            $minSupporters = $motionType->getAmendmentSupportTypeClass()->getMinNumberOfSupporters();
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
            return \Yii::t('amend', 'button_submit_create');
        } elseif ($this->getMyConsultation()->getSettings()->screeningAmendments) {
            return \Yii::t('amend', 'button_submit_submit');
        } else {
            return \Yii::t('amend', 'button_submit_publish');
        }
    }

    /**
     */
    public function setInitialSubmitted()
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

    /**
     */
    public function setScreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix == '') {
            $numbering         = $this->getMyConsultation()->getAmendmentNumbering();
            $this->titlePrefix = $numbering->getAmendmentNumber($this, $this->getMyMotion());
        }
        $this->save(true);
        $this->onPublish();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_UNSCREEN, $this->id);
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
        ConsultationLog::logCurrUser($consultation, ConsultationLog::AMENDMENT_PUBLISH_PROPOSAL, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Amendment::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::AMENDMENT_DELETE, $this->id);
    }

    /**
     */
    public function onPublish()
    {
        $this->flushCacheWithChildren();
        $this->setTextFixedIfNecessary();

        $init   = $this->getInitiators();
        $initId = (count($init) > 0 ? $init[0]->userId : null);
        ConsultationLog::log($this->getMyConsultation(), $initId, ConsultationLog::AMENDMENT_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            new AmendmentPublishedNotification($this);
        }
    }

    /**
     * @param bool $save
     */
    public function setTextFixedIfNecessary($save = true)
    {
        if ($this->getMyConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleAmendmentStati())) {
            return;
        }
        $this->textFixed = 1;
        if ($save) {
            $this->save(true);
        }
    }

    /**
     *
     */
    public function flushCacheWithChildren()
    {
        $this->flushCache();
        \Yii::$app->cache->delete($this->getPdfCacheKey());
        foreach ($this->sections as $section) {
            $section->flushCache();
        }
    }

    /**
     * @return string
     */
    public function getPdfCacheKey()
    {
        return 'amendment-pdf-' . $this->id;
    }

    /**
     * @param bool
     * @return string
     */
    public function getFilenameBase($noUmlaut)
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

    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
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
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
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

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    /**
     * @return array
     */
    public function getDataTable()
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) == 1) {
            $first = $inits[0];
            if ($first->personType == MotionSupporter::PERSON_ORGANIZATION && $first->resolutionDate > 0) {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->organization;
                $return[\Yii::t('export', 'ResolutionDate')]  = Tools::formatMysqlDate($first->resolutionDate, null, false);
            } else {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('export', 'InitiatorMulti')] = implode("\n", $initiators);
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati(true))) {
            $return[\Yii::t('motion', 'status')] = IMotion::getStatusNames()[$this->status];
        }

        return $return;
    }

    /**
     * @return ConsultationMotionType
     */
    public function getMyMotionType()
    {
        return $this->getMyMotion()->motionType;
    }

    /**
     * @param ConsultationMotionType $motionType
     * @throws FormError
     */
    public function setMotionType(ConsultationMotionType $motionType)
    {
        if (!$this->getMyMotion()->motionType->isCompatibleTo($motionType)) {
            throw new FormError('This amendment cannot be changed to the type ' . $motionType->titleSingular);
        }

        $typeMapping = $this->getMyMotion()->motionType->getSectionCompatibilityMapping($motionType);
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

    /**
     * @return int
     */
    public function getLikeDislikeSettings()
    {
        return $this->getMyMotion()->motionType->amendmentLikesDislikes;
    }

    /**
     * @return boolean
     */
    public function isDeadlineOver()
    {
        return $this->getMyMotion()->motionType->amendmentDeadlineIsOver();
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
     * return boolean
     */
    public function hasAlternativeProposaltext()
    {
        return ($this->proposalStatus == Amendment::STATUS_MODIFIED_ACCEPTED && $this->proposalReference);
    }

    /**
     * @return string
     */
    public function getFormattedStatus()
    {
        $statiNames = Amendment::getStatusNames();
        $status     = '';
        switch ($this->status) {
            case Amendment::STATUS_SUBMITTED_UNSCREENED:
            case Amendment::STATUS_SUBMITTED_UNSCREENED_CHECKED:
                $status = '<span class="unscreened">' . Html::encode($statiNames[$this->status]) . '</span>';
                break;
            case Amendment::STATUS_SUBMITTED_SCREENED:
                $status = '<span class="screened">' . \Yii::t('amend', 'screened_hint') . '</span>';
                break;
            case Amendment::STATUS_COLLECTING_SUPPORTERS:
                $status = Html::encode($statiNames[$this->status]);
                $status .= ' <small>(' . \Yii::t('motion', 'supporting_permitted') . ': ';
                $status .= IPolicy::getPolicyNames()[$this->getMyMotionType()->policySupportAmendments] . ')</small>';
                break;
            default:
                $status .= Html::encode($statiNames[$this->status]);
        }
        if (trim($this->statusString) != '') {
            $status .= " <small>(" . Html::encode($this->statusString) . ")</small>";
        }
        return $status;
    }

    /**
     * @return string
     */
    public function getFormattedProposalStatus()
    {
        if ($this->proposalStatus === null || $this->proposalStatus == 0) {
            return '';
        }
        switch ($this->proposalStatus) {
            case Amendment::STATUS_REFERRED:
                return \Yii::t('amend', 'refer_to') . ': ' . Html::encode($this->proposalComment);
            case Amendment::STATUS_OBSOLETED_BY:
                $refAmend = $this->getMyConsultation()->getAmendment($this->proposalComment);
                if ($refAmend) {
                    $refAmendStr = Html::a($refAmend->getShortTitle(), UrlHelper::createAmendmentUrl($refAmend));
                    return \Yii::t('amend', 'obsoleted_by') . ': ' . $refAmendStr;
                } else {
                    return static::getProposalStatiAsVerbs()[$this->proposalStatus];
                }
                break;
            case Amendment::STATUS_CUSTOM_STRING:
                return Html::encode($this->proposalComment);
                break;
            default:
                if (isset(static::getProposalStatiAsVerbs()[$this->proposalStatus])) {
                    return static::getProposalStatiAsVerbs()[$this->proposalStatus];
                } else {
                    return $this->proposalStatus . '?';
                }
        }
    }

    /**
     * @param boolean $includeVoted
     * @return Amendment[]
     */
    public function collidesWithOtherProposedAmendments($includeVoted)
    {
        $collidesWith = [];

        if ($this->proposalReference) {
            $sections = $this->proposalReference->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        } else {
            $sections = $this->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE);
        }
        $newSections = [];
        foreach ($sections as $section) {
            $newSections[$section->sectionId] = $section->data;
        }

        foreach ($this->getMyMotion()->getAmendmentsProposedToBeIncluded($includeVoted, [$this]) as $amendment) {
            foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $coll = $section->getRewriteCollissions($newSections[$section->sectionId], false);
                if (count($coll) > 0) {
                    if (!in_array($amendment, $collidesWith)) {
                        $collidesWith[] = $amendment;
                    }
                }
            }
        }

        return $collidesWith;
    }

    /**
     * @return bool
     */
    public function proposalStatusNeedsUserFeedback()
    {
        if ($this->proposalStatus === null || $this->proposalStatus == Amendment::STATUS_ACCEPTED) {
            return false;
        } else {
            return true;
        }
    }
}
