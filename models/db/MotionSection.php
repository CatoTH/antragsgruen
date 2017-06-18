<?php

namespace app\models\db;

use app\components\diff\AmendmentDiffMerger;
use app\components\HashedStaticCache;
use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\sectionTypes\ISectionType;
use app\models\exceptions\Internal;

/**
 * @package app\models\db
 *
 * @property int $motionId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $cache
 * @property string $metadata
 *
 * @property MotionComment[] $comments
 * @property AmendmentSection[] $amendingSections
 */
class MotionSection extends IMotionSection
{
    use CacheTrait;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'motionSection';
    }

    /**
     * @return ConsultationSettingsMotionSection|null
     */
    public function getSettings()
    {
        $motion = $this->getMotion();
        if ($motion) {
            foreach ($motion->motionType->motionSections as $section) {
                if ($section->id == $this->sectionId) {
                    return $section;
                }
            }
        } else {
            /** @var ConsultationSettingsMotionSection $section */
            $section = ConsultationSettingsMotionSection::findOne($this->sectionId);
            foreach ($section->motionType->motionSections as $section) {
                if ($section->id == $this->sectionId) {
                    return $section;
                }
            }
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(MotionComment::class, ['motionId' => 'motionId', 'sectionId' => 'sectionId'])
            ->where('status != ' . IntVal(IComment::STATUS_DELETED));
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->getMotion($this->motionId)) {
            return $current;
        } else {
            /** @var Motion $motion */
            $motion = Motion::findOne($this->motionId);
            if ($motion) {
                return Consultation::findOne($motion->consultationId);
            } else {
                /** @var ConsultationSettingsMotionSection $section */
                $section = ConsultationSettingsMotionSection::findOne($this->sectionId);
                return $section->motionType->getConsultation();
            }
        }
    }

    /**
     * @return Motion
     */
    public function getMotion()
    {
        return $this->getConsultation()->getMotion($this->motionId);
    }

    /**
     * @return AmendmentSection[]|null
     */
    public function getAmendingSections()
    {
        $sections = [];
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        foreach ($motion->amendments as $amend) {
            if (in_array($amend->status, $this->getConsultation()->getInvisibleAmendmentStati(true))) {
                continue;
            }
            foreach ($amend->sections as $section) {
                if ($section->sectionId == $this->sectionId) {
                    $sections[] = $section;
                }
            }
        }
        return $sections;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['motionId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    /**
     * @return \string[][]
     * @throws Internal
     */
    public function getTextParagraphLines()
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }

        $lineLength = $this->getConsultation()->getSettings()->lineLength;
        $cacheDeps  = [$lineLength, $this->data];
        $cache      = HashedStaticCache::getCache('getTextParagraphLines', $cacheDeps);
        if ($cache) {
            return $cache;
        }

        $paragraphs      = HTMLTools::sectionSimpleHTML($this->data);
        $paragraphsLines = [];
        foreach ($paragraphs as $paraNo => $paragraph) {
            $lines                    = LineSplitter::splitHtmlToLines($paragraph, $lineLength, '###LINENUMBER###');
            $paragraphsLines[$paraNo] = $lines;
        }

        HashedStaticCache::setCache('getTextParagraphLines', $cacheDeps, $paragraphsLines);

        return $paragraphsLines;
    }

    /**
     * @var MotionSectionParagraph[]
     */
    private $paragraphObjectCacheWithLines    = null;
    private $paragraphObjectCacheWithoutLines = null;

    /**
     * @param bool $lineNumbers
     * @param bool $includeComments
     * @param bool $includeAmendment
     * @return MotionSectionParagraph[]
     * @throws Internal
     */
    public function getTextParagraphObjects($lineNumbers, $includeComments = false, $includeAmendment = false)
    {
        if ($lineNumbers && $this->paragraphObjectCacheWithLines !== null) {
            return $this->paragraphObjectCacheWithLines;
        }
        if (!$lineNumbers && $this->paragraphObjectCacheWithoutLines !== null) {
            return $this->paragraphObjectCacheWithoutLines;
        }
        /** @var MotionSectionParagraph[] $return */
        $return = [];
        $paras  = $this->getTextParagraphLines();
        foreach ($paras as $paraNo => $para) {
            $paragraph              = new MotionSectionParagraph();
            $paragraph->paragraphNo = $paraNo;
            $paragraph->lines       = $para;
            $paragraph->origStr     = str_replace('###LINENUMBER###', '', implode('', $para));

            if ($includeAmendment) {
                $paragraph->amendmentSections = [];
            }

            if ($includeComments) {
                $paragraph->comments = [];
                foreach ($this->comments as $comment) {
                    if ($comment->paragraph == $paraNo) {
                        $paragraph->comments[] = $comment;
                    }
                }
            }

            $return[$paraNo] = $paragraph;
        }
        if ($includeAmendment) {
            $motion = $this->getConsultation()->getMotion($this->motionId);
            foreach ($motion->getVisibleAmendments(false) as $amendment) {
                if ($amendment->globalAlternative) {
                    continue;
                }
                $amSec = null;
                foreach ($amendment->getActiveSections() as $section) {
                    if ($section->sectionId == $this->sectionId) {
                        $amSec = $section;
                    }
                }
                if (!$amSec) {
                    continue;
                }
                $paragraphs   = HTMLTools::sectionSimpleHTML($this->data);
                $amParagraphs = $amSec->diffToOrigParagraphs($paragraphs);
                foreach ($amParagraphs as $amParagraph) {
                    $return[$amParagraph->origParagraphNo]->amendmentSections[] = $amParagraph;
                }
            }
        }
        if ($includeComments && $includeAmendment) {
            if ($lineNumbers) {
                $this->paragraphObjectCacheWithLines = $return;
            } else {
                $this->paragraphObjectCacheWithoutLines = $return;
            }
        }
        return $return;
    }

    /**
     * @return string
     * @throws Internal
     */
    public function getTextWithLineNumberPlaceholders()
    {
        $return = '';
        $paras  = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $return .= implode('', $para) . "\n";
        }
        $return = trim($return);
        return $return;
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            return 0;
        }
        if (!$this->getSettings()->lineNumbers) {
            return 0;
        }

        $cached = $this->getCacheItem('getNumberOfCountableLines');
        if ($cached !== null) {
            return $cached;
        }

        $num   = 0;
        $paras = $this->getTextParagraphLines();
        foreach ($paras as $para) {
            $num += count($para);
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
        $motion   = $this->getConsultation()->getMotion($this->motionId);
        $lineNo   = $motion->getFirstLineNumber();
        $sections = $motion->getSortedSections();
        foreach ($sections as $section) {
            /** @var MotionSection $section */
            if ($section->sectionId == $this->sectionId) {
                return $lineNo;
            } else {
                $lineNo += $section->getNumberOfCountableLines();
            }
        }
        throw new Internal('Did not find myself: Motion ' . $this->motionId . ' / Section ' . $this->sectionId);
    }

    /** @var null|AmendmentDiffMerger */
    private $amendmentDiffMerger = null;

    /**
     * @param int[] $toMergeAmendmentIds
     *
     * @return AmendmentDiffMerger
     */
    public function getAmendmentDiffMerger($toMergeAmendmentIds)
    {
        if (is_null($this->amendmentDiffMerger)) {
            $sections = [];
            foreach ($this->amendingSections as $section) {
                if (in_array($section->amendmentId, $toMergeAmendmentIds)) {
                    $sections[] = $section;
                }
            }

            $merger = new AmendmentDiffMerger();
            $merger->initByMotionSection($this);
            $merger->addAmendingSections($sections);
            $merger->mergeParagraphs();
            $this->amendmentDiffMerger = $merger;
        }
        return $this->amendmentDiffMerger;
    }
}
