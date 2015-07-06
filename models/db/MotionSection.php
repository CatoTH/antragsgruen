<?php

namespace app\models\db;

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
 * @property string $metadata
 *
 * @property Motion $motion
 * @property ConsultationSettingsMotionSection $consultationSetting
 * @property MotionComment[] $comments
 */
class MotionSection extends IMotionSection
{

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
            [['motionId', 'sectionId'], 'required'],
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
     * @param bool $lineNumbers
     * @return MotionSectionParagraph[]
     * @throws Internal
     */
    public function getTextParagraphObjects($lineNumbers)
    {
        /** @var MotionSectionParagraph[] $return */
        $return = [];
        $paras  = $this->getTextParagraphs();
        foreach ($paras as $paraNo => $para) {
            $lineLength = $this->consultationSetting->motionType->consultation->getSettings()->lineLength;
            $linesOut   = LineSplitter::motionPara2lines($para, $lineNumbers, $lineLength);

            $paragraph              = new MotionSectionParagraph();
            $paragraph->paragraphNo = $paraNo;
            $paragraph->lines       = $linesOut;
            $paragraph->amendments  = [];

            $paragraph->comments = [];
            foreach ($this->comments as $comment) {
                if ($comment->paragraph == $paraNo) {
                    $paragraph->comments[] = $comment;
                }
            }

            $return[] = $paragraph;
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
            $lines      = implode(' ', $linesOut) . "\n";
            $lines      = str_replace('###FORCELINEBREAK### ', '<br>', $lines);
            $return .= $lines;
        }
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

        $num   = 0;
        $paras = $this->getTextParagraphs();
        foreach ($paras as $para) {
            $lineLength = $this->consultationSetting->motionType->consultation->getSettings()->lineLength;
            $linesOut   = LineSplitter::motionPara2lines($para, true, $lineLength);
            $num += count($linesOut);
        }
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
            if ($section->sectionId == $this->sectionId) {
                return $lineNo;
            } else {
                $lineNo += $section->getNumberOfCountableLines();
            }
        }
        throw new Internal('Did not find myself');
    }
}
