<?php

namespace app\models\db;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\exceptions\FormError;
use app\models\sectionTypes\Image;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextHTML;
use app\models\sectionTypes\TextSimple;
use app\models\sectionTypes\Title;
use yii\db\ActiveRecord;
use app\models\exceptions\Internal;
use yii\helpers\Html;

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
 */
class MotionSection extends ActiveRecord
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
     * @return bool
     */
    public function checkLength()
    {
        // @TODO
        return true;
    }

    /**
     * @return ISectionType
     * @throws Internal
     */
    public function getSectionType()
    {
        switch ($this->consultationSetting->type) {
            case ISectionType::TYPE_TITLE:
                return new Title($this);
            case ISectionType::TYPE_TEXT_HTML:
                return new TextHTML($this);
            case ISectionType::TYPE_TEXT_SIMPLE:
                return new TextSimple($this);
            case ISectionType::TYPE_IMAGE:
                return new Image($this);
        }
        throw new Internal('Unknown Field Type: ' . $this->consultationSetting->type);
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
        foreach ($paras as $para) {
            $lineLength = $this->consultationSetting->consultation->getSettings()->lineLength;
            if (mb_stripos($para, '<ul>') === 0 || mb_stripos($para, '<ol>') === 0 ||
                mb_stripos($para, '<blockquote>') === 0
            ) {
                $lineLength -= 20;
            }
            $splitter = new \app\components\LineSplitter($para, $lineLength);
            $linesIn  = $splitter->splitLines(false, true);

            if ($lineNumbers) {
                $linesOut = [];
                $pres     = ['<p>', '<ul><li>', '<ol><li>', '<blockquote><p>'];
                $linePre  = '###LINENUMBER###';
                foreach ($linesIn as $line) {
                    $inserted = false;
                    foreach ($pres as $pre) {
                        if (mb_stripos($line, $pre) === 0) {
                            $inserted = true;
                            $line     = str_ireplace($pre, $pre . $linePre, $line);
                        }
                    }
                    if (!$inserted) {
                        $line = $linePre . $line;
                    }
                    $linesOut[] = $line;
                }
            } else {
                $linesOut = $linesIn;
            }

            $paragraph        = new MotionSectionParagraph();
            $paragraph->lines = $linesOut;
            $return[]         = $paragraph;
        }
        return $return;
    }

    public function getFirstLineNo()
    {
        return 1; // @TODO
    }
}
