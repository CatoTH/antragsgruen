<?php

namespace app\models\db;

use app\components\MotionSorter;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\components\EmailNotifications;
use app\models\exceptions\Internal;
use app\models\policies\All;
use Yii;
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
     * @return Consultation
     */
    public function getConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getMotion($this->id)) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
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
        return $this->getConsultation(); // @TODO remove this method
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getMySections()
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
            [['id', 'consultationId', 'motionTypeId', 'status', 'textFixed', 'agendaItemId'], 'number'],
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
        $query->where('motion.status = ' . static::STATUS_SUBMITTED_UNSCREENED);
        $query->andWhere('motion.consultationId = ' . IntVal($consultation->id));
        $query->orderBy("dateCreation DESC");

        return $query->all();
    }


    /**
     * @return string
     */
    public function getTitleWithPrefix()
    {
        if ($this->getConsultation()->getSettings()->hideTitlePrefix) {
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
    public function getNewTitlePrefix()
    {
        return $this->titlePrefix . 'neu'; // @TODO
    }

    /**
     * @param bool $includeWithdrawn
     * @return Amendment[]
     */
    public function getVisibleAmendments($includeWithdrawn = true)
    {
        $amendments = [];
        foreach ($this->amendments as $amend) {
            if (!in_array($amend->status, $this->getConsultation()->getInvisibleAmendmentStati(!$includeWithdrawn))) {
                $amendments[] = $amend;
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
        return MotionSorter::getSortedAmendments($this->getConsultation(), $amendments);
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
                $currUser        = User::getCurrentUser();
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId > 0) {
                    $hadLoggedInUser = true;
                    if ($currUser && $currUser->id == $supp->userId) {
                        return true;
                    }
                }
                if ($supp->role == MotionSupporter::ROLE_INITIATOR && $supp->userId === null) {
                    if ($currUser->hasPrivilege($this->getMyConsultation(), User::PRIVILEGE_MOTION_EDIT)) {
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

        if ($this->getConsultation()->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
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
        if (!in_array($this->status, [Motion::STATUS_SUBMITTED_SCREENED, Motion::STATUS_SUBMITTED_UNSCREENED])) {
            return false;
        }
        return $this->iAmInitiator();
    }

    /**
     * @return bool
     */
    public function canMergeAmendments()
    {
        if ($this->iAmInitiator()) {
            return true;
        }
        if (User::currentUserHasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }
        return false;
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
        $supporters    = count($this->getSupporters());
        $minSupporters = $this->motionType->getMotionSupportTypeClass()->getMinNumberOfSupporters();
        return ($supporters >= $minSupporters);
    }

    /**
     * @return bool
     */
    public function isSocialSharable()
    {
        if ($this->getConsultation()->site->getSettings()->forceLogin) {
            return false;
        }
        if (in_array($this->status, $this->getConsultation()->getInvisibleMotionStati(true))) {
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

        if ($this->getConsultation()->getSettings()->lineNumberingGlobal) {
            $motions      = $this->getConsultation()->getVisibleMotions(false);
            $motionBlocks = MotionSorter::getSortedMotions($this->getConsultation(), $motions);
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
        if (in_array($this->status, $this->getConsultation()->getInvisibleMotionStati())) {
            $this->status = static::STATUS_DELETED;
        } else {
            $this->status = static::STATUS_WITHDRAWN;
        }
        $this->save();
        $this->flushCacheStart();
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::MOTION_WITHDRAW, $this->id);
    }

    /**
     */
    public function setInitialSubmitted()
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

        if ($needsCollectionPhase) {
            $this->status = Motion::STATUS_COLLECTING_SUPPORTERS;
        } elseif ($this->getConsultation()->getSettings()->screeningMotions) {
            $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        } else {
            $this->status = Motion::STATUS_SUBMITTED_SCREENED;
            if ($this->statusString == '') {
                $this->titlePrefix = $this->getConsultation()->getNextMotionPrefix($this->motionTypeId);
            }
        }
        $this->save();

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this));
        $mailText   = str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->title, $motionLink, $this->getInitiatorsStr()],
            \Yii::t('motion', 'submitted_adminnoti_body')
        );

        // @TODO Use different texts depending on the status
        $this->getConsultation()->sendEmailToAdmins(\Yii::t('motion', 'submitted_adminnoti_title'), $mailText);
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix == '') {
            $this->titlePrefix = $this->getConsultation()->getNextMotionPrefix($this->motionTypeId);
        }
        $this->save(true);
        $this->onPublish();
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::MOTION_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::MOTION_UNSCREEN, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Motion::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->getConsultation(), ConsultationLog::MOTION_DELETE, $this->id);
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
        ConsultationLog::log($this->getConsultation(), $initId, ConsultationLog::MOTION_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $motionType = UserNotification::NOTIFICATION_NEW_MOTION;
            $notified   = [];
            foreach ($this->getConsultation()->userNotifications as $noti) {
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
        if ($this->getConsultation()->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->getConsultation()->getInvisibleMotionStati())) {
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
        if ($this->getConsultation()->cacheOneMotionAffectsOthers()) {
            $this->getConsultation()->flushCacheWithChildren();
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
            $first = $inits[0];
            if ($first->personType == MotionSupporter::PERSON_ORGANIZATION && $first->resolutionDate > 0) {
                $return[\Yii::t('export', 'InitiatorSingle')] = $first->organization;
                $return[\Yii::t('export', 'ResolutionDate')]  = Tools::formatMysqlDate($first->resolutionDate);
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

        return $return;
    }

    /**
     * @param string $prefix
     * @param null|Amendment $ignore
     * @return null|Amendment
     */
    public function findAmendmentWithPrefix($prefix, $ignore = null)
    {
        $numbering = $this->getConsultation()->getAmendmentNumbering();
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
     * @return int
     */
    public function getLikeDislikeSettings()
    {
        return $this->motionType->motionLikesDislikes;
    }
}
