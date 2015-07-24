<?php

namespace app\models\db;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
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
 * @property string $changeMetatext
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property string $cache
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property Motion $motion
 * @property AmendmentComment[] $comments
 * @property AmendmentSupporter[] $amendmentSupporters
 * @property AmendmentSection[] $sections
 */
class Amendment extends IMotion implements IRSSItem
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['amendmentId' => 'id'])
            ->andWhere(AmendmentComment::tableName() . '.status != ' . AmendmentComment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendmentSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(AmendmentSection::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['id', 'motionId', 'status', 'textFixed'], 'number'],
        ];
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . ' zu ' . $this->motion->title;
        } else {
            if ($this->motion->titlePrefix != '') {
                return $this->titlePrefix . ' zu ' . $this->motion->titlePrefix . ': ' . $this->motion->title;
            } else {
                return $this->titlePrefix . ' zu ' . $this->motion->title;
            }
        }
    }

    /**
     * @return string
     */
    public function getShortTitle()
    {
        if ($this->getMyConsultation()->getSettings()->hideTitlePrefix) {
            return $this->titlePrefix . ' zu ' . $this->motion->title;
        } else {
            if ($this->motion->titlePrefix != '') {
                return $this->titlePrefix . ' zu ' . $this->motion->titlePrefix;
            } else {
                return $this->titlePrefix . ' zu ' . $this->motion->title;
            }
        }
    }

    /**
     * @return Consultation
     */
    public function getMyConsultation()
    {
        return $this->motion->consultation;
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getMySections()
    {
        return $this->motion->motionType->motionSections;
    }


    private $firstDiffLine = null;

    /**
     * @return int
     */
    public function getFirstDiffLine()
    {
        if ($this->firstDiffLine === null) {
            foreach ($this->sections as $section) {
                if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                    continue;
                }
                $formatter = new AmendmentSectionFormatter($section, \app\components\diff\Diff::FORMATTING_CLASSES);
                $diff      = $formatter->getGroupedDiffLinesWithNumbers();
                if (count($diff) > 0) {
                    return $diff[0]['lineFrom'];
                }
            }

            // Nothing changed in a simple text section
            $this->firstDiffLine = $this->motion->getFirstLineNumber();
        }
        return $this->firstDiffLine;
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
        if (count($tit1) == 3 && count($tit2) == 3) {
            if ($tit1[2] < $tit2[2]) {
                return -1;
            }
            if ($tit1[2] > $tit2[2]) {
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
        $ams = array();
        foreach ($amendments as $am) {
            if (!in_array($am->status, $consultation->getInvisibleAmendmentStati())) {
                $am->getFirstDiffLine();
                $ams[] = $am;
            }
        }

        usort($ams, [Amendment::className(), 'sortVisibleByLineNumbersSort']);

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
                'motion' => function ($query) use ($invisibleStati, $consultation) {
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
        $query->where('amendment.status = ' . static::STATUS_SUBMITTED_UNSCREENED);
        $query->joinWith(
            [
                'motion' => function ($query) use ($consultation) {
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
            return true;
        }

        if ($this->textFixed) {
            return false;
        }

        if ($this->motion->consultation->getSettings()->iniatorsMayEdit && $this->iAmInitiator()) {
            if ($this->motion->motionType->amendmentDeadlineIsOver()) {
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
        if (!in_array($this->status, [Amendment::STATUS_SUBMITTED_SCREENED, Amendment::STATUS_SUBMITTED_UNSCREENED])) {
            return false;
        }
        return $this->iAmInitiator();
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        return 0; // @TODO
    }

    /**
     * @param bool $lineNumbers
     * @return MotionSectionParagraphAmendment[]
     */
    public function getChangedParagraphs($lineNumbers)
    {
        $paragraphs = [];
        foreach ($this->motion->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $paras = $section->getTextParagraphObjects($lineNumbers, true, true);
            foreach ($paras as $para) {
                foreach ($para->amendmentSections as $amSec) {
                    if ($amSec->amendmentSection->amendmentId == $this->id) {
                        $paragraphs[] = $amSec;
                    }
                }
            }
        }
        return $paragraphs;
    }

    /**
     */
    public function withdraw()
    {
        $this->status = static::STATUS_WITHDRAWN;
        $this->save();
        $this->motion->consultation->flushCaches();
        // @TODO Log changes
    }

    /**
     */
    public function setScreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_SCREENED;
        $this->save(true);
        $this->onPublish();
        // @TODO Log changes
    }

    /**
     */
    public function setUnscreened()
    {
        $this->status = Amendment::STATUS_SUBMITTED_UNSCREENED;
        $this->save();
        // @TODO Log changes
    }

    /**
     */
    public function setDeleted()
    {
        $this->status = Amendment::STATUS_DELETED;
        $this->save();
        // @TODO Log changes
    }

    /**
     */
    public function onPublish()
    {
        $this->flushCaches();
        /*
        // @TODO Prevent duplicate Calls
        $notified = [];
        foreach ($this->consultation->subscriptions as $sub) {
            if ($sub->motions && !in_array($sub->userId, $notified)) {
                $sub->user->notifyMotion($this);
                $notified[] = $sub->userId;
            }
        }
        */
    }

    /**
     */
    public function flushCaches()
    {
        $this->cache = '';
        $this->motion->flushCaches();
    }

    /**
     * @param RSSExporter $feed
     */
    public function addToFeed(RSSExporter $feed)
    {
        // @TODO Inline styling
        $content = '';
        foreach ($this->sections as $section) {
            if ($section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
                continue;
            }
            $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_INLINE);
            $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();

            if (count($diffGroups) > 0) {
                $content .= '<h2>' . Html::encode($section->consultationSetting->title) . '</h2>';
                $content .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
                $content .= \app\models\sectionTypes\TextSimple::formatDiffGroup($diffGroups);
                $content .= '</div>';
                $content .= '</section>';
            }
        }

        if ($this->changeExplanation) {
            $content .= '<h2>Begründung</h2>';
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
                $return['Antragsteller/in'] = $first->name;
                $return['Beschlussdatum']   = Tools::formatMysqlDate($first->resolutionDate);
            } else {
                $return['Antragsteller/in'] = $first->getNameWithResolutionDate(false);
            }
        } else {
            $initiators = [];
            foreach ($this->getInitiators() as $init) {
                $initiators[] = $init->getNameWithResolutionDate(false);
            }
            $return['Antragsteller/innen'] = implode("\n", $initiators);
        }

        return $return;
    }

    /**
     * @return Content
     */
    public function getTexContent()
    {
        $content              = new Content();
        $content->template    = $this->motion->motionType->texTemplate->texContent;
        $content->title       = $this->motion->title;
        $content->titlePrefix = $this->titlePrefix . ' zu ' . $this->motion->titlePrefix;
        $content->titleLong   = $this->getTitle();

        $intro                    = explode("\n", $this->motion->consultation->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
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
            $content->text .= $section->getSectionType()->getAmendmentTeX();
        }

        $title = Exporter::encodePlainString('Begründung');
        $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $lines = LineSplitter::motionPara2lines($this->changeExplanation, false, PHP_INT_MAX);
        $content->text .= TextSimple::getMotionLinesToTeX($lines) . "\n";

        return $content;
    }
}
