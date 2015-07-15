<?php

namespace app\models\db;

use app\components\latex\Content;
use app\components\latex\Exporter;
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
 * @property ConsultationSettingsTag[] $tags
 * @property MotionSection[] $sections
 * @property MotionSupporter[] $motionSupporters
 * @property ConsultationAgendaItem $agendaItem
 */
class Motion extends IMotion implements IRSSItem
{
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
        return $this->hasMany(MotionComment::className(), ['motionId' => 'id'])
            ->andWhere(MotionComment::tableName() . '.status != ' . MotionComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSupporters()
    {
        return $this->hasMany(MotionSupporter::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasMany(Amendment::className(), ['motionId' => 'id'])
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
        return $this->hasMany(ConsultationSettingsTag::className(), ['id' => 'tagId'])
            ->viaTable('motionTag', ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(MotionSection::className(), ['motionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionType()
    {
        return $this->hasOne(ConsultationMotionType::className(), ['id' => 'motionTypeId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItem()
    {
        return $this->hasOne(ConsultationAgendaItem::className(), ['id' => 'agendaItemId']);
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
        if ($this->consultation->getSettings()->hideRevision) {
            return $this->title;
        }

        $name = $this->titlePrefix;
        if (strlen($name) > 1 && !in_array($name[strlen($name) - 1], array(":", "."))) {
            $name .= ":";
        }
        $name .= " " . $this->title;
        return $name;
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
        // @TODO This is probably too simple...
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
     * @return string
     */
    public function getTypeName()
    {
        // @TODO Tags
        return Yii::t('motion', 'Antrag');
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        $num = 0;
        foreach ($this->getSortedSections() as $section) {
            $num += $section->getNumberOfCountableLines();
        }
        return $num;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        if ($this->consultation->getSettings()->lineNumberingGlobal) {
            $motionBlocks = MotionSorter::getSortedMotions($this->consultation, $this->consultation->motions);
            $lineNo       = 1;
            foreach ($motionBlocks as $motions) {
                foreach ($motions as $motion) {
                    /** @var Motion $motion */
                    if ($motion->id == $this->id) {
                        return $lineNo;
                    } else {
                        $lineNo += $motion->getNumberOfCountableLines();
                    }
                }
            }
            throw new Internal('Did not find myself');
        } else {
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
        $this->consultation->flushCaches();
        // @TODO Log changes
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_SCREENED;
        $this->save(true);
        $this->onPublish();
        // @TODO Log changes
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Motion::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        // @TODO Log changes
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Motion::STATUS_DELETED;
        $this->save();
        // @TODO Log changes
    }

    /**
     *
     */
    public function onPublish()
    {
        $this->flushCaches();
        // @TODO Prevent duplicate Calls
        $notified = [];
        foreach ($this->consultation->subscriptions as $sub) {
            if ($sub->motions && !in_array($sub->userId, $notified)) {
                $sub->user->notifyMotion($this);
                $notified[] = $sub->userId;
            }
        }
    }

    /**
     *
     */
    public function flushCaches()
    {
        $this->cache = '';
        $this->consultation->flushCaches();
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

        $initiators = [];
        foreach ($this->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        if (count($initiators) == 1) {
            $return['Antragsteller/in'] = implode("\n", $initiators);
        } else {
            $return['Antragsteller/innen'] = implode("\n", $initiators);
        }
        if (count($this->tags) > 1) {
            $tags = [];
            foreach ($this->tags as $tag) {
                $tags[] = $tag->title;
            }
            $return['Themen'] = implode("\n", $tags);
        } elseif (count($this->tags) == 1) {
            $return['Thema'] = $this->tags[0]->title;
        }

        return $return;
    }

    /**
     * @return Content
     */
    public function getTexContent()
    {
        $content                  = new Content();
        $content->template        = $this->motionType->texTemplate->texContent;
        $intro                    = explode("\n", $this->consultation->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        $content->title           = $this->title;
        $content->titlePrefix     = $this->titlePrefix;
        $content->titleLong       = $this->title;
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        } else {
            $content->introductionSmall = '';
        }
        $initiators = [];
        foreach ($this->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        $content->motionDataTable = '';
        foreach ($this->getDataTable() as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        $content->text = '';
        foreach ($this->getSortedSections(true) as $section) {
            $content->text .= $section->getSectionType()->getMotionTeX();
        }

        return $content;
    }
}
