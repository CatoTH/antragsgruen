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
use app\models\notifications\MotionSubmitted as MotionSubmittedNotification;
use app\models\notifications\MotionWithdrawn as MotionWithdrawnNotification;
use app\models\policies\All;
use app\models\policies\IPolicy;
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
 * @property string $dateCreation
 * @property string $datePublication
 * @property string $dateResolution
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
 * @property MotionAdminComment[] $adminComments
 * @property ConsultationSettingsTag[] $tags
 * @property MotionSection[] $sections
 * @property MotionSupporter[] $motionSupporters
 * @property ConsultationAgendaItem $agendaItem
 * @property Motion $replacedMotion
 * @property Motion[] $replacedByMotions
 */
class Motion extends IMotion implements IRSSItem
{
    use CacheTrait;

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
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdminComments()
    {
        return $this->hasMany(MotionAdminComment::class, ['motionId' => 'id'])
            ->andWhere(MotionAdminComment::tableName() . '.status != ' . MotionAdminComment::STATUS_DELETED);
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
     * @param int $amendmentId
     * @return Amendment|null
     */
    public function getAmendment($amendmentId)
    {
        foreach ($this->amendments as $amendment) {
            if ($amendment->id == $amendmentId && $amendment->status != Amendment::STATUS_DELETED) {
                return $amendment;
            }
        }
        return null;
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
     * @return Motion[]
     */
    public static function getNewestByConsultation(Consultation $consultation, $limit = 5)
    {
        $invisibleStati = array_map('IntVal', $consultation->getInvisibleMotionStati());

        $query = Motion::find();
        $query->where('motion.status NOT IN (' . implode(', ', $invisibleStati) . ')');
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");
        $query->offset(0)->limit($limit);

        return $query->all();
    }

    /**
     * @param Consultation $consultation
     * @return Motion[]
     */
    public static function getScreeningMotions(Consultation $consultation)
    {
        $query = Motion::find();
        $query->where('motion.status IN (' . implode(', ', static::getScreeningStati()) . ')');
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }


    /**
     * @return string
     */
    public function getTitleWithPrefix()
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->title;
        }

        $name = $this->titlePrefix;
        if (strlen($name) > 1 && !in_array($name[strlen($name) - 1], array(':', '.'))) {
            $name .= ':';
        }
        $name .= ' ' . $this->title;
        return $name;
    }

    /**
     * @return string
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
     * @return string
     */
    public function getNewTitlePrefix()
    {
        $new = \Yii::t('motion', 'prefix_new_code');
        if (stripos($this->titlePrefix, $new) !== false) {
            $parts = explode($new, $this->titlePrefix);
            if ($parts[1] > 0) {
                $parts[1]++;
            } else {
                $parts[1] = 2;
            }
            return implode($new, $parts);
        } else {
            return $this->titlePrefix . $new;
        }
    }

    /**
     * @param bool $includeWithdrawn
     * @return Amendment[]
     */
    public function getVisibleAmendments($includeWithdrawn = true)
    {
        $amendments = [];
        foreach ($this->amendments as $amend) {
            if (!in_array($amend->status, $this->getMyConsultation()->getInvisibleAmendmentStati(!$includeWithdrawn))) {
                $amendments[] = $amend;
            }
        }
        return $amendments;
    }

    /**
     * @param null|Amendment[] $exclude
     * @return Amendment[]
     */
    public function getAmendmentsRelevantForCollissionDetection($exclude = null)
    {
        $amendments = [];
        foreach ($this->amendments as $amendment) {
            if ($exclude && in_array($amendment, $exclude)) {
                continue;
            }
            if ($amendment->isVisibleForAdmins() && $amendment->status != Amendment::STATUS_DRAFT) {
                $amendments[] = $amendment;
            }
        }
        return $amendments;
    }

    /**
     * @param bool $includeWithdrawn
     * @return Amendment[]
     */
    public function getVisibleAmendmentsSorted($includeWithdrawn = true)
    {
        $amendments = $this->getVisibleAmendments($includeWithdrawn);
        return MotionSorter::getSortedAmendments($this->getMyConsultation(), $amendments);
    }

    /**
     * @param bool $screeningAdmin
     * @return MotionComment[]
     */
    public function getVisibleComments($screeningAdmin)
    {
        $visibleStati = [MotionComment::STATUS_VISIBLE];
        if ($screeningAdmin) {
            $visibleStati[] = MotionComment::STATUS_SCREENING;
        }
        $comments = [];
        foreach ($this->comments as $comment) {
            if (in_array($comment->status, $visibleStati)) {
                $comments[] = $comment;
            }
        }
        return $comments;
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
            if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId == $user->id) {
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
            foreach ($this->motionSupporters as $supp) {
                $currUser = User::getCurrentUser();
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId > 0) {
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
                if ($this->motionType->getMotionPolicy()->getPolicyID() == All::getPolicyID()) {
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
            if ($this->motionType->motionDeadlineIsOver()) {
                return false;
            } else {
                if (count($this->getVisibleAmendments()) > 0) {
                    return false;
                } else {
                    return true;
                }
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
            Motion::STATUS_SUBMITTED_SCREENED,
            Motion::STATUS_SUBMITTED_UNSCREENED,
            Motion::STATUS_COLLECTING_SUPPORTERS
        ])
        ) {
            return false;
        }
        return $this->iAmInitiator();
    }

    /**
     * @return bool
     */
    public function canMergeAmendments()
    {
        $replacedByMotions = array_filter($this->replacedByMotions, function (Motion $motion) {
            $draftStati = [
                Motion::STATUS_DRAFT,
                Motion::STATUS_MERGING_DRAFT_PUBLIC,
                Motion::STATUS_MERGING_DRAFT_PRIVATE
            ];
            return !in_array($motion->status, $draftStati);
        });
        if (count($replacedByMotions) > 0) {
            return false;
        }
        if (User::currentUserHasPrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
            return true;
        }
        return false;
    }

    /**
     * @param boolean $onlyPublic
     * @return Motion|null
     */
    public function getMergingDraft($onlyPublic)
    {
        if ($onlyPublic) {
            $status = [Motion::STATUS_MERGING_DRAFT_PUBLIC];
        } else {
            $status = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];
        }
        return Motion::findOne([
            'parentMotionId' => $this->id,
            'status'         => $status,
        ]);
    }

    /**
     * @param bool $allowAdmins
     * @param bool $assumeLoggedIn
     * @param bool $throwExceptions
     * @return bool
     * @throws NotAmendable
     */
    public function isCurrentlyAmendable($allowAdmins = true, $assumeLoggedIn = false, $throwExceptions = false)
    {
        $iAmAdmin = User::currentUserHasPrivilege($this->getMyConsultation(), User::PRIVILEGE_ANY);

        if (!($allowAdmins && $iAmAdmin)) {
            if ($this->nonAmendable) {
                if ($throwExceptions) {
                    throw new NotAmendable('Not amendable in the current state', false);
                } else {
                    return false;
                }
            }
            $notAmendableStati = [
                static::STATUS_DELETED,
                static::STATUS_DRAFT,
                static::STATUS_COLLECTING_SUPPORTERS,
                static::STATUS_SUBMITTED_UNSCREENED,
                static::STATUS_SUBMITTED_UNSCREENED_CHECKED,
                static::STATUS_DRAFT_ADMIN,
                static::STATUS_MODIFIED,
            ];
            if (in_array($this->status, $notAmendableStati)) {
                if ($throwExceptions) {
                    throw new NotAmendable('Not amendable in the current state', false);
                } else {
                    return false;
                }
            }
            if ($this->motionType->amendmentDeadlineIsOver()) {
                if ($throwExceptions) {
                    throw new NotAmendable(\Yii::t('structure', 'policy_deadline_over'), true);
                } else {
                    return false;
                }
            }
        }
        $policy  = $this->motionType->getAmendmentPolicy();
        $allowed = $policy->checkCurrUser($allowAdmins, $assumeLoggedIn);

        if (!$allowed) {
            if ($throwExceptions) {
                $msg    = $policy->getPermissionDeniedAmendmentMsg();
                $public = ($msg != '' && $policy->getPolicyID() != IPolicy::POLICY_NOBODY);
                throw new NotAmendable($msg, $public);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function canFinishSupportCollection()
    {
        if (!$this->iAmInitiator()) {
            return false;
        }
        if ($this->status != Motion::STATUS_COLLECTING_SUPPORTERS) {
            return false;
        }
        if ($this->isDeadlineOver()) {
            return false;
        }
        $supporters    = count($this->getSupporters());
        $minSupporters = $this->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
        return ($supporters >= $minSupporters);
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
     * @throws Internal
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
                    if ($motion->id == $this->id) {
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
            if ($supp->role == MotionSupporter::ROLE_INITIATOR) {
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
            if ($supp->role == MotionSupporter::ROLE_SUPPORTER) {
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
            if ($supp->role == MotionSupporter::ROLE_LIKE) {
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
            if ($supp->role == MotionSupporter::ROLE_DISLIKE) {
                $return[] = $supp;
            }
        };
        return $return;
    }

    /**
     */
    public function withdraw()
    {
        if ($this->status == Motion::STATUS_DRAFT) {
            $this->status = static::STATUS_DELETED;
        } elseif (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati())) {
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
                if ($initiator->personType == ISupporter::PERSON_ORGANIZATION) {
                    $isOrganization = true;
                }
            }
            $supporters    = count($this->getSupporters());
            $minSupporters = $this->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
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
        if ($this->titlePrefix == '') {
            $this->titlePrefix = $this->getMyConsultation()->getNextMotionPrefix($this->motionTypeId);
        }
        $this->save(true);
        $this->onPublish();
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
    public function setDeleted()
    {
        $this->status = Motion::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getMyConsultation(), ConsultationLog::MOTION_DELETE, $this->id);
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
            $motionType = UserNotification::NOTIFICATION_NEW_MOTION;
            $notified   = [];
            foreach ($this->getMyConsultation()->userNotifications as $noti) {
                if ($noti->notificationType == $motionType && !in_array($noti->userId, $notified)) {
                    $noti->user->notifyMotion($this);
                    $notified[]             = $noti->userId;
                    $noti->lastNotification = date('Y-m-d H:i:s');
                    $noti->save();
                }
            }
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            EmailNotifications::sendMotionOnPublish($this);
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
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati())) {
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
     * @return string
     */
    public function getDate()
    {
        return $this->dateCreation;
    }

    /**
     * @param bool $skipAgenda
     * @return array
     */
    public function getDataTable($skipAgenda = false)
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) == 1) {
            $first          = $inits[0];
            $resolutionDate = $first->resolutionDate;
            if ($first->personType == MotionSupporter::PERSON_ORGANIZATION && $resolutionDate > 0) {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->organization;
                $return[\Yii::t('export', 'ResolutionDate')]  = Tools::formatMysqlDate($resolutionDate, null, false);
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
        if ($this->agendaItem && !$skipAgenda) {
            $return[\Yii::t('export', 'AgendaItem')] = $this->agendaItem->getShownCode(true) .
                ' ' . $this->agendaItem->title;
        }
        if (count($this->tags) > 1) {
            $tags = [];
            foreach ($this->tags as $tag) {
                $tags[] = $tag->title;
            }
            $return[\Yii::t('export', 'TopicMulti')] = implode("\n", $tags);
        } elseif (count($this->tags) == 1) {
            $return[\Yii::t('export', 'TopicSingle')] = $this->tags[0]->title;
        }
        if (in_array($this->status, $this->getMyConsultation()->getInvisibleMotionStati(true))) {
            $return[\Yii::t('motion', 'status')] = IMotion::getStati()[$this->status];
        }

        return $return;
    }

    /**
     * @param string $prefix
     * @param null|Amendment $ignore
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
        return $this->motionType;
    }

    /**
     * @param ConsultationMotionType $motionType
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
        return $this->motionType->motionDeadlineIsOver();
    }
}
