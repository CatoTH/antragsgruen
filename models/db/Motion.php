<?php

namespace app\models\db;

use app\components\Mail;
use app\components\MotionSorter;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\exceptions\Internal;
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
 */
class Motion extends IMotion implements IRSSItem
{
    use CacheTrait;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motion';
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
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
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
     * @return Amendment[]
     */
    public function getSortedAmendments()
    {
        $aes = $this->amendments;
        usort(
            $aes,
            function ($ae1, $ae2) {
                /** @var Amendment $ae1 */
                /** @var Amendment $ae2 */
                return strnatcasecmp(strtolower($ae1->titlePrefix), strtolower($ae2->titlePrefix));
            }
        );
        return $aes;
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
     * @return Consultation
     */
    public function getMyConsultation()
    {
        return $this->consultation;
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
        if ($this->consultation->getSettings()->hideTitlePrefix) {
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
     * @return Amendment[]
     */
    public function getVisibleAmendments()
    {
        $amendments = [];
        foreach ($this->amendments as $amend) {
            if (!in_array($amend->status, $this->consultation->getInvisibleAmendmentStati())) {
                $amendments[] = $amend;
            }
        }
        return $amendments;
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
            return true;
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->motionType->motionDeadlineIsOver()) {
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
        if (User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING)) {
            return true;
        }
        return false;
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

        if ($this->consultation->getSettings()->lineNumberingGlobal) {
            $motionBlocks = MotionSorter::getSortedMotions($this->consultation, $this->consultation->motions);
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
            throw new Internal('Did not find myself');
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
        $this->status = static::STATUS_WITHDRAWN;
        $this->save();
        $this->consultation->flushCacheWithChildren();
        ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_WITHDRAW, $this->id);
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_SCREENED;
        if ($this->titlePrefix == '') {
            $this->titlePrefix = $this->consultation->getNextMotionPrefix($this->motionTypeId);
        }
        $this->save(true);
        $this->onPublish();
        ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_SCREEN, $this->id);
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_UNSCREEN, $this->id);
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Motion::STATUS_DELETED;
        $this->save();
        ConsultationLog::logCurrUser($this->consultation, ConsultationLog::MOTION_DELETE, $this->id);
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
        ConsultationLog::log($this->consultation, $initId, ConsultationLog::MOTION_PUBLISH, $this->id);

        if ($this->datePublication === null) {
            $motionType = UserNotification::NOTIFICATION_NEW_MOTION;
            $notified   = [];
            foreach ($this->consultation->userNotifications as $noti) {
                if ($noti->notificationType == $motionType && !in_array($noti->userId, $notified)) {
                    $noti->user->notifyMotion($this);
                    $notified[]             = $noti->userId;
                    $noti->lastNotification = date('Y-m-d H:i:s');
                    $noti->save();
                }
            }
            $this->datePublication = date('Y-m-d H:i:s');
            $this->save();

            if ($this->consultation->getSettings()->initiatorConfirmEmails) {
                $initiator = $this->getInitiators();
                if (count($initiator) > 0 && $initiator[0]->contactEmail != '') {
                    $text       = "Hallo,\n\ndein Antrag wurde soeben auf Antragsgrün veröffentlicht. " .
                        "Du kannst ihn hier einsehen: %LINK%\n\n" .
                        "Mit freundlichen Grüßen,\n" .
                        "  Das Antragsgrün-Team";
                    $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this));
                    Mail::sendWithLog(
                        EMailLog::TYPE_MOTION_SUBMIT_CONFIRM,
                        $this->consultation->site,
                        trim($initiator[0]->contactEmail),
                        null,
                        'Antrag veröffentlicht',
                        str_replace('%LINK%', $motionLink, $text)
                    );
                }
            }
        }
    }

    /**
     * @param bool $save
     */
    public function setTextFixedIfNecessary($save = true)
    {
        if ($this->consultation->getSettings()->adminsMayEdit) {
            return;
        }
        if (in_array($this->status, $this->consultation->getInvisibleMotionStati())) {
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
        foreach ($this->sections as $section) {
            $section->flushCache();
        }
        foreach ($this->amendments as $amend) {
            $amend->flushCacheWithChildren();
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
            $content .= '<h2>' . Html::encode($section->consultationSetting->title) . '</h2>';
            $content .= $section->getSectionType()->getSimple();
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
     * @return array
     */
    public function getDataTable()
    {
        $return = [];

        $inits = $this->getInitiators();
        if (count($inits) == 1) {
            $first = $inits[0];
            if ($first->personType == MotionSupporter::PERSON_ORGANIZATION && $first->resolutionDate > 0) {
                $return[\Yii::t('pdf', 'InitiatorSingle')] = $first->name;
                $return[\Yii::t('pdf', 'ResolutionDate')]  = Tools::formatMysqlDate($first->resolutionDate);
            } else {
                $return[\Yii::t('pdf', 'InitiatorSingle')] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return[\Yii::t('pdf', 'InitiatorMulti')] = implode("\n", $initiators);
        }
        if ($this->agendaItem) {
            $return[\Yii::t('pdf', 'AgendaItem')] = $this->agendaItem->code . ' ' . $this->agendaItem->title;
        }
        if (count($this->tags) > 1) {
            $tags = [];
            foreach ($this->tags as $tag) {
                $tags[] = $tag->title;
            }
            $return[\Yii::t('pdf', 'TopicMulti')] = implode("\n", $tags);
        } elseif (count($this->tags) == 1) {
            $return[\Yii::t('pdf', 'InitiatorSingle')] = $this->tags[0]->title;
        }

        return $return;
    }
}
