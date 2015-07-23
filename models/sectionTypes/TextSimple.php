<?php

namespace app\models\sectionTypes;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\components\HTMLTools;
use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\components\opendocument\Text;
use app\controllers\Base;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use yii\helpers\Html;
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
    public function getSimple()
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="paragraph"><div class="text">' . $section . '</div></div>';
        }
        return $str;
    }


    /**
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(\TCPDF $pdf)
    {
        /** @var MotionSection $section */
        $section = $this->section;

        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML('<h3>' . $this->section->consultationSetting->title . '</h3>');

        $lineLength = $section->consultationSetting->motionType->consultation->getSettings()->lineLength;
        $linenr     = $section->getFirstLineNumber();
        $textSize   = ($lineLength > 70 ? 10 : 11);
        $pdf->SetFont('Courier', '', $textSize);
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
            $text2 = implode('<br>', $lineNos);

            $y = $pdf->getY();
            $pdf->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);
            $pdf->writeHTMLCell(173, '', 24, '', implode('<br>', $linesArr), 0, 1, 0, true, '', true);

            $pdf->Ln(7);
        }
    }

    /**
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(\TCPDF $pdf)
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_INLINE);
        $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();
        if (count($diffGroups) > 0) {
            $html = static::formatDiffGroup($diffGroups);
            $pdf->writeHTMLCell(170, '', 27, '', $html, 0, 1, 0, true, '', true);
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

    /**
     * @param array $diffGroups
     * @param string $wrapStart
     * @param string $wrapEnd
     * @param int $firstLineOfSection
     * @return string
     */
    public static function formatDiffGroup($diffGroups, $wrapStart = '', $wrapEnd = '', $firstLineOfSection = -1)
    {
        $out = '';
        foreach ($diffGroups as $diff) {
            $text      = $diff['text'];
            $hasInsert = (mb_strpos($text, '<ins>') !== false || mb_strpos($text, 'class="inserted"') !== false);
            $hasDelete = (mb_strpos($text, '<del>') !== false || mb_strpos($text, 'class="deleted"') !== false);
            $out .= $wrapStart;
            $out .= '<h4 class="lineSummary">';
            if ($diff['newLine']) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= 'Nach Zeile #LINETO#:';
                } elseif ($hasDelete) {
                    $out .= 'Nach Zeile #LINETO# löschen:';
                } elseif ($hasInsert) {
                    if ($diff['lineTo'] < $firstLineOfSection) {
                        $out .= str_replace('#LINE#', ($diff['lineTo'] + 1), 'Vor Zeile #LINE# einfügen:');
                    } else {
                        $out .= 'Nach Zeile #LINETO# einfügen:';
                    }
                }
            } elseif ($diff['lineFrom'] == $diff['lineTo']) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= 'In Zeile #LINETO#:';
                } elseif ($hasDelete) {
                    $out .= 'In Zeile #LINETO# löschen:';
                } elseif ($hasInsert) {
                    $out .= 'In Zeile #LINETO# einfügen:';
                }
            } else {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= 'Von Zeile #LINEFROM# bis #LINETO#:';
                } elseif ($hasDelete) {
                    $out .= 'Von Zeile #LINEFROM# bis #LINETO# löschen:';
                } elseif ($hasInsert) {
                    $out .= 'Von Zeile #LINEFROM# bis #LINETO# einfügen:';
                }
            }
            $out = str_replace(['#LINETO#', '#LINEFROM#'], [$diff['lineTo'], $diff['lineFrom']], $out) . '</h4>';
            if ($diff['text'][0] != '<') {
                $out .= '<p>' . $diff['text'] . '</p>';
            } else {
                $out .= $diff['text'];
            }
            $out .= $wrapEnd;
        }
        return $out;
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    /**
     * @param string[] $lines
     * @return string
     */
    public static function getMotionLinesToTeX($lines)
    {
        $str = implode('###LINEBREAK###', $lines);
        $str = str_replace('###FORCELINEBREAK######LINEBREAK###', '###FORCELINEBREAK###', $str);
        $str = Exporter::encodeHTMLString($str);
        $str = str_replace('###LINENUMBER###', '', $str);
        $str = str_replace('###LINEBREAK###', "\\linebreak\n", $str);
        $str = str_replace('###FORCELINEBREAK###\linebreak', '\newline', $str);
        return $str;
    }

    /**
     * @return string
     */
    public function getMotionTeX()
    {
        $tex = '';

        /** @var MotionSection $section */
        $section = $this->section;

        $hasLineNumbers = $section->consultationSetting->lineNumbers;

        $title = Exporter::encodePlainString($section->consultationSetting->title);
        $tex .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";

        if ($section->consultationSetting->fixedWidth || $hasLineNumbers) {
            if ($hasLineNumbers) {
                $tex .= "\\linenumbers\n";
                $tex .= "\\resetlinenumber[" . $section->getFirstLineNumber() . "]\n";
            }

            $paragraphs = $section->getTextParagraphObjects($hasLineNumbers);
            foreach ($paragraphs as $paragraph) {
                $tex .= static::getMotionLinesToTeX($paragraph->lines) . "\n";
            }

            if ($hasLineNumbers) {
                $tex .= "\n\\nolinenumbers\n";
            }
        } else {
            $paras = $section->getTextParagraphs();
            foreach ($paras as $para) {
                $lines = LineSplitter::motionPara2lines($para, false, PHP_INT_MAX);
                $tex .= static::getMotionLinesToTeX($lines) . "\n";
            }
        }

        return $tex;
    }

    /**
     * @return string
     */
    public function getAmendmentTeX()
    {
        $tex = '';

        /** @var AmendmentSection $section */
        $section = $this->section;

        $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_CLASSES);
        $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();

        if (count($diffGroups) > 0) {
            $title = Exporter::encodePlainString($section->consultationSetting->title);
            $tex .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $html = TextSimple::formatDiffGroup($diffGroups);
            $tex .= Exporter::encodeHTMLString($html);
        }

        return $tex;
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return $this->section->data;
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $formatter  = new AmendmentSectionFormatter($section, Diff::FORMATTING_CLASSES);
        $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();
        $diff = static::formatDiffGroup($diffGroups);
        $diff = str_replace('<h4', '<br><h4', $diff);
        $diff = str_replace('</h4>', '</h4><br>', $diff);
        $diff = str_replace('###FORCELINEBREAK###', '<br>', $diff);
        if (mb_substr($diff, 0, 4) == '<br>') {
            $diff = mb_substr($diff, 4);
        }
        return $diff;
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $section = $this->section;
        /** @var MotionSection $section */
        $odt->addHtmlTextBlock('<h2>' . Html::encode($section->consultationSetting->title) . '</h2>', false);
        if ($section->consultationSetting->lineNumbers) {
            $paragraphs = $section->getTextParagraphObjects(true, false, false);
            foreach ($paragraphs as $paragraph) {
                $html = implode('<br>', $paragraph->lines);
                $html = str_replace('###LINENUMBER###', '', $html);
                $html = str_replace('###FORCELINEBREAK###', '', $html);
                if (mb_substr($html, 0, 1) != '<') {
                    $html = '<p>' . $html . '</p>';
                }

                $odt->addHtmlTextBlock($html, true);
            }
        } else {
            $paras = $section->getTextParagraphs();
            foreach ($paras as $para) {
                $lines = LineSplitter::motionPara2lines($para, false, PHP_INT_MAX);
                $odt->addHtmlTextBlock(implode('<br>', $lines), false);
            }
        }
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->consultationSetting->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[ÄNDERUNG]', false); // @TODO
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        $data = strip_tags($this->section->data);
        return (mb_stripos($data, $text) !== false);
    }
}
