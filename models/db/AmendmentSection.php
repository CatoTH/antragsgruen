<?php

namespace app\models\db;

use app\components\diff\Diff;
use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

/**
 * @package app\models\db
 *
 * @property int $amendmentId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $metadata
 *
 * @property Amendment $amendment
 * @property ConsultationSettingsMotionSection $consultationSetting
 * @property AmendmentSection
 */
class AmendmentSection extends IMotionSection
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentSection';
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
    public function getAmendment()
    {
        return $this->hasOne(Amendment::className(), ['id' => 'amendmentId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'sectionId'], 'required'],
            [['amendmentId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
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
     * @return string
     * @throws Internal
     */
    protected function getHtmlDiffWithLineNumbers()
    {
        if ($this->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Only supported for simple HTML');
        }
        $strPre = null;
        foreach ($this->amendment->motion->sections as $section) {
            if ($section->sectionId == $this->sectionId) {
                $strPre = $section->getTextWithLineNumberPlaceholders();
            }
        }
        if ($strPre === null) {
            throw new Internal('Original version not found');
        }
        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');

        $strPost = implode("\n", $this->getTextParagraphs()) . "\n";

        return $diff->computeDiff($strPre, $strPost);
    }


    /**
     * @return string
     * @throws Internal
     */
    public function getInlineDiffHtml()
    {
        $lineOffset = $this->getFirstLineNo() - 1;
        $computed = $this->getHtmlDiffWithLineNumbers();

        $computedLines = explode('###LINENUMBER###', $computed);
        $out = $computedLines[0];
        for ($currLine = 1; $currLine < count($computedLines); $currLine++) {
            $out .= '<span class="lineNumber" data-line-number="' . ($currLine + $lineOffset) . '"></span>';
            $out .= $computedLines[$currLine];
            $out .= '<br>';
        }
        $out = str_replace('<li><br>', '<li>', $out);
        $out = str_replace('<blockquote><br>', '<blockquote>', $out);
        return $out;
    }



    /**
     * @return int
     */
    public function getFirstLineNo()
    {
        return 1; // @TODO
    }

    /**
     * @return string
     * @throws Internal
     */
    public function getDiffLinesWithNumbers()
    {
        $lineOffset = $this->getFirstLineNo() - 1;
        $computed = $this->getHtmlDiffWithLineNumbers();
        $computedLines = explode('###LINENUMBER###', $computed);

        $inIns = $inDel = false;
        $affectedLines = [];
        for ($currLine = 1; $currLine < count($computedLines); $currLine++) {
            $hadDiff = false;
            if (preg_match_all('/<\/?(ins|del)>/siu', $computedLines[$currLine], $matches, PREG_OFFSET_CAPTURE)) {
                $hadDiff = true;
                foreach ($matches[0] as $found) {
                    switch ($found[0]) {
                        case '<ins>':
                            $inIns = true;
                            break;
                        case '</ins>':
                            $inIns = false;
                            break;
                        case '<del>':
                            $inDel = true;
                            break;
                        case '</del>':
                            $inDel = false;
                            break;
                        default:
                            throw new Internal('Unknown token: ' . $found[0]);
                    }
                }
            }
            if ($inIns || $inDel || $hadDiff) {
                $line = $computedLines[$currLine];
                $line = preg_replace('/<\/?(li|ul|ol|blockquote|p)>/siu', '', $line);
                $line = '<span class="lineNumber" data-line-number="' . ($currLine + $lineOffset) . '"></span>' . $line;
                $affectedLines[$currLine] = $line;
            }
        }
        return implode("<br>", $affectedLines);
    }
}
