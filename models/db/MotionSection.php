<?php

namespace app\models\db;

use app\components\diff\Diff;
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
 * @property string $cache
 * @property string $metadata
 *
 * @property Motion $motion
 * @property ConsultationSettingsMotionSection $consultationSetting
 * @property MotionComment[] $comments
 */
class MotionSection extends IMotionSection
{
    use CacheTrait;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motionSection';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationSetting()
    {
        return $this->hasOne(ConsultationSettingsMotionSection::className(), ['id' => 'sectionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(MotionComment::className(), ['motionId' => 'motionId', 'sectionId' => 'sectionId'])
            ->where('status != ' . IntVal(IComment::STATUS_DELETED));
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId'], 'required'],
            [['motionId', 'sectionId'], 'number'],
        ];
    }

    /**
     * @return \string[]
     * @throws Internal
     */
    public function getTextParagraphs()
    {
        if ($this->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }
        return HTMLTools::sectionSimpleHTML($this->data);
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
        $paras  = $this->getTextParagraphs();
        foreach ($paras as $paraNo => $para) {
            $lineLength = $this->consultationSetting->motionType->consultation->getSettings()->lineLength;
            $linesOut   = LineSplitter::motionPara2lines($para, $lineNumbers, $lineLength);

            $paragraph              = new MotionSectionParagraph();
            $paragraph->paragraphNo = $paraNo;
            $paragraph->lines       = $linesOut;

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
            foreach ($this->motion->amendments as $amendment) {
                $amSec = null;
                foreach ($amendment->sections as $section) {
                    if ($section->sectionId == $this->sectionId) {
                        $amSec = $section;
                    }
                }
                if (!$amSec) {
                    continue;
                }
                $diff         = new Diff();
                $amParagraphs = $diff->computeAmendmentParagraphDiff($paras, $amSec);
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
        $paras  = $this->getTextParagraphs();
        foreach ($paras as $para) {
            $lineLength = $this->consultationSetting->motionType->consultation->getSettings()->lineLength;
            $linesOut   = LineSplitter::motionPara2lines($para, true, $lineLength);
            $return .= implode(' ', $linesOut) . "\n";
        }
        $return = trim($return);
        return $return;
    }

    /**
     * @return int
     */
    public function getNumberOfCountableLines()
    {
        if ($this->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            return 0;
        }
        if (!$this->consultationSetting->lineNumbers) {
            return 0;
        }

        $cached = $this->getCacheItem('getNumberOfCountableLines');
        if ($cached !== null) {
            return $cached;
        }

        $num   = 0;
        $paras = $this->getTextParagraphs();
        foreach ($paras as $para) {
            $lineLength = $this->consultationSetting->motionType->consultation->getSettings()->lineLength;
            $linesOut   = LineSplitter::motionPara2lines($para, true, $lineLength);
            $num += count($linesOut);
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
        $lineNo   = $this->motion->getFirstLineNumber();
        $sections = $this->motion->getSortedSections();
        foreach ($sections as $section) {
            /** @var MotionSection $section */
            if ($section->sectionId == $this->sectionId) {
                return $lineNo;
            } else {
                $lineNo += $section->getNumberOfCountableLines();
            }
        }
        throw new Internal('Did not find myself');
    }
}
