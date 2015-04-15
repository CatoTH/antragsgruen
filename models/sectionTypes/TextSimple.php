<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\controllers\Base;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use yii\web\View;

class TextSimple extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(false);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        return $this->getTextAmendmentFormField(false);
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $this->section->data = HTMLTools::cleanSimpleHtml($data);
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section          = $this->section;
        $section->data    = HTMLTools::cleanSimpleHtml($data['consolidated']);
        $section->dataRaw = $data['raw'];
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="content">' . $section . '</div>';
        }
        return $str;
    }


    /**
     * @param \TCPDF $pdf
     */
    public function printToPDF(\TCPDF $pdf)
    {
        /** @var MotionSection $section */
        $section = $this->section;

        $lineLength = $section->consultationSetting->consultation->getSettings()->lineLength;
        $linenr     = $section->getFirstLineNo();
        $textSize   = ($lineLength > 70 ? 10 : 11);
        $pdf->SetFont("Courier", "", $textSize);
        $pdf->Ln(7);

        $hasLineNumbers = $section->consultationSetting->lineNumbers;
        $paragraphs     = $section->getTextParagraphObjects($hasLineNumbers);
        foreach ($paragraphs as $paragraph) {
            $linesArr = [];
            foreach ($paragraph->lines as $line) {
                $linesArr[] = str_replace('###LINENUMBER###', '', $line);
            }

            $lineNos = [];
            for ($i = 0; $i < count($paragraph->lines); $i++) {
                $lineNos[] = $linenr++;
            }
            $text2 = implode("<br>", $lineNos);

            $y = $pdf->getY();
            $pdf->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);
            $pdf->writeHTMLCell(173, '', 24, '', implode('<br>', $linesArr), 0, 1, 0, true, '', true);

            $pdf->Ln(7);
        }
    }

    /**
     * @param Base $controller
     * @param CommentForm $commentForm
     * @param int[] $openedComments
     * @return string
     */
    public function showMotionView(Base $controller, $commentForm, $openedComments)
    {
        $view = new View();
        return $view->render(
            '@app/views/motion/showSimpleTextSection',
            [
                'section'        => $this->section,
                'openedComments' => $openedComments,
                'commentForm'    => $commentForm,
            ],
            $controller
        );
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
