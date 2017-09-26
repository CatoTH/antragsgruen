<?php

namespace app\models\sectionTypes;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DiffRenderer;
use app\components\HashedStaticCache;
use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\UrlHelper;
use app\controllers\Base;
use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;
use yii\web\View;
use CatoTH\HTML2OpenDocument\Text;

class TextSimple extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        return $this->getTextMotionFormField(false, $this->section->getSettings()->fixedWidth);
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth                           = $this->section->getSettings()->fixedWidth;
        if ($this->section->getSettings()->motionType->amendmentMultipleParagraphs) {
            $pre = ($this->section->dataRaw ? $this->section->dataRaw : $this->section->data);
            return $this->getTextAmendmentFormField(false, $pre, $fixedWidth);
        } else {
            return $this->getTextAmendmentFormFieldSingleParagraph($fixedWidth);
        }
    }

    /**
     * @param bool $fixedWidth
     * @return string
     */
    public function getTextAmendmentFormFieldSingleParagraph($fixedWidth)
    {
        /** @var AmendmentSection $amSection */
        $amSection = $this->section;
        $moSection = $amSection->getOriginalMotionSection();
        $moParas   = HTMLTools::sectionSimpleHTML($moSection->data, false);

        $amParas          = $moParas;
        $changedParagraph = -1;
        if (!$amSection->isNewRecord) {
            foreach ($amSection->diffToOrigParagraphs($moParas, false) as $paraNo => $para) {
                $amParas[$paraNo] = $para->strDiff;
                $changedParagraph = $paraNo;
            }
        }

        $type = $this->section->getSettings();
        $str  = '<div class="label">' . Html::encode($type->title) . '</div>';
        $str  .= '<div class="texteditorBox" data-section-id="' . $amSection->sectionId . '" ' .
            'data-changed-para-no="' . $changedParagraph . '">';
        foreach ($moParas as $paraNo => $moPara) {
            $nameBase = 'sections[' . $type->id . '][' . $paraNo . ']';
            $htmlId   = 'sections_' . $type->id . '_' . $paraNo;
            $holderId = 'section_holder_' . $type->id . '_' . $paraNo;

            $str .= '<div class="form-group wysiwyg-textarea single-paragraph" id="' . $holderId . '"';
            $str .= ' data-max-len="' . $type->maxLen . '" data-full-html="0"';
            $str .= ' data-original="' . Html::encode($moPara) . '"';
            $str .= ' data-paragraph-no="' . $paraNo . '"';
            $str .= '><label for="' . $htmlId . '" class="hidden">' . Html::encode($type->title) . '</label>';

            $str .= '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
                'title="' . Html::encode($type->title) . '"></textarea>';
            $str .= '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
                'title="' . Html::encode($type->title) . '"></textarea>';
            $str .= '<div class="texteditor';
            if ($fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '" data-track-changed="1" data-enter-mode="br" data-no-strike="1" id="' . $htmlId . '_wysiwyg" ' .
                'title="' . Html::encode($type->title) . '">';
            $str .= $amParas[$paraNo];
            $str .= '</div>';

            $str .= '<div class="modifiedActions"><a href="#" class="revert">';
            $str .= \Yii::t('amend', 'revert_changes');
            $str .= '</a></div>';

            $str .= '</div>';
        }
        $str .= '</div>';

        return $str;
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $type                   = $this->section->getSettings();
        $this->section->dataRaw = $data;
        $this->section->data    = HTMLTools::cleanSimpleHtml($data, $type->getForbiddenMotionFormattings());
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        $post    = \Yii::$app->request->post();

        if ($section->getSettings()->motionType->amendmentMultipleParagraphs) {
            $section->data    = HTMLTools::stripEmptyBlockParagraphs(HTMLTools::cleanSimpleHtml($data['consolidated']));
            $section->dataRaw = $data['raw'];
        } else {
            $moSection = $section->getOriginalMotionSection();
            $paras     = HTMLTools::sectionSimpleHTML($moSection->data, false);
            $parasRaw  = $paras;
            if ($post['modifiedParagraphNo'] !== '' && $post['modifiedSectionId'] == $section->sectionId) {
                $paraNo            = IntVal($post['modifiedParagraphNo']);
                $paras[$paraNo]    = $post['sections'][$section->sectionId][$paraNo]['consolidated'];
                $parasRaw[$paraNo] = $post['sections'][$section->sectionId][$paraNo]['raw'];
            }
            $section->data    = HTMLTools::cleanSimpleHtml(implode('', $paras));
            $section->dataRaw = implode('', $parasRaw);
        }
    }

    /**
     * @param bool $isRight
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSimple($isRight)
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="paragraph"><div class="text';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '">' . $section . '</div></div>';
        }
        return $str;
    }

    /**
     * @return string
     */
    public function getMotionPlainHtml()
    {
        $html = $this->section->data;
        $html = str_replace('<span class="underline">', '<span style="text-decoration: underline;">', $html);
        $html = str_replace('<span class="strike">', '<span style="text-decoration: line-through;">', $html);
        return $html;
    }

    /**
     * @return string
     */
    public function getAmendmentPlainHtml()
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        if ($section->getAmendment()->globalAlternative) {
            $str = '<h3>' . Html::encode($section->getSettings()->title) . '</h3>';
            $str .= '<p><strong>' . \Yii::t('amend', 'global_alternative') . '</strong></p>';
            $str .= $section->data;
        } else {
            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);
            if (count($diffGroups) == 0) {
                return '';
            }

            $str = '<h3>' . Html::encode($section->getSettings()->title) . '</h3>';
            $str .= TextSimple::formatDiffGroup($diffGroups, '', '', $firstLine);
        }
        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormattedGlobalAlternative()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        $str = '<div id="section_' . $section->sectionId . '" class="motionTextHolder">';
        $str .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        $str .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';

        $htmlSections = HTMLTools::sectionSimpleHTML($section->data);
        foreach ($htmlSections as $htmlSection) {
            $str .= '<div class="paragraph"><div class="text';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '">' . $htmlSection . '</div></div>';
        }

        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $this->getAmendmentFormattedGlobalAlternative();
        }
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;
        $firstLine  = $section->getFirstLineNumber();

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

        if (count($diffGroups) == 0) {
            return '';
        }

        $str       = '<div id="section_' . $section->sectionId . '" class="motionTextHolder">';
        $str       .= '<h3 class="green">' . Html::encode($section->getSettings()->title) . '</h3>';
        $str       .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
        $wrapStart = '<section class="paragraph"><div class="text';
        if ($section->getSettings()->fixedWidth) {
            $wrapStart .= ' fixedWidthFont';
        }
        $wrapStart .= '">';
        $wrapEnd   = '</div></section>';
        $str       .= TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);
        $str       .= '</div>';
        $str       .= '</div>';

        return $str;
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     * @throws \app\models\exceptions\Internal
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $lineLength = $section->getConsultation()->getSettings()->lineLength;
        $linenr     = $section->getFirstLineNumber();
        $textSize   = ($lineLength > 70 ? 10 : 11);
        if ($section->getSettings()->fixedWidth) {
            $pdf->SetFont('dejavusansmono', '', $textSize);
        } else {
            $pdf->SetFont('helvetica', '', $textSize);
        }
        $pdf->Ln(7);

        $hasLineNumbers = $section->getSettings()->lineNumbers;
        if ($section->getSettings()->fixedWidth || $hasLineNumbers) {
            $paragraphs = $section->getTextParagraphObjects($hasLineNumbers);
            foreach ($paragraphs as $paragraph) {
                $linesArr = [];
                foreach ($paragraph->lines as $line) {
                    $line       = str_replace('###LINENUMBER###', '', $line);
                    $line       = preg_replace('/<br>\s*$/siu', '', $line);
                    $linesArr[] = $line . '';
                }

                if ($hasLineNumbers) {
                    $lineNos = [];
                    for ($i = 0; $i < count($paragraph->lines); $i++) {
                        $lineNos[] = $linenr++;
                    }
                    $text2 = implode('<br>', $lineNos);
                } else {
                    $text2 = '';
                }

                $y = $pdf->getY();
                $pdf->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);
                $text1 = implode('<br>', $linesArr);

                // instead of <span class="strike"></span> TCPDF can only handle <s></s>
                // for striking through text
                $pattern = '/<span class="strike">(.*)<\/span>/iUs';
                $replace = '<s>${1}</s>';
                $text1   = preg_replace($pattern, $replace, $text1);

                $pdf->writeHTMLCell(173, '', 24, $y, $text1, 0, 1, 0, true, '', true);

                $pdf->Ln(7);
            }
        } else {
            $paras = $section->getTextParagraphLines();
            foreach ($paras as $para) {
                $html = str_replace('###LINENUMBER###', '', implode('', $para));
                $y    = $pdf->getY();
                $pdf->writeHTMLCell(12, '', 12, $y, '', 0, 0, 0, true, '', true);
                $pdf->writeHTMLCell(173, '', 24, '', $html, 0, 1, 0, true, '', true);

                $pdf->Ln(7);
            }
        }
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->getAmendment()->globalAlternative) {
            if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
                $pdfLayout->printSectionHeading($this->section->getSettings()->title);
                $pdf->ln(7);
            }

            $pdf->writeHTMLCell(170, '', 24, '', $section->data, 0, 1, 0, true, '', true);
        } else {
            $firstLine  = $section->getFirstLineNumber();
            $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);

            if (count($diffGroups) > 0) {
                if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
                    $pdfLayout->printSectionHeading($this->section->getSettings()->title);
                    $pdf->ln(7);
                }

                $html               = static::formatDiffGroup($diffGroups);
                $replaces           = [];
                $replaces['<ins ']  = '<span ';
                $replaces['</ins>'] = '</span>';
                $replaces['<del ']  = '<span ';
                $replaces['</del>'] = '</span>';
                $html               = str_replace(array_keys($replaces), array_values($replaces), $html);

                // instead of <span class="strike"></span> TCPDF can only handle <s></s>
                // for striking through text
                $pattern = '/<span class="strike">(.*)<\/span>/iUs';
                $replace = '<s>${1}</s>';
                $html    = preg_replace($pattern, $replace, $html);

                $pdf->writeHTMLCell(170, '', 24, '', $html, 0, 1, 0, true, '', true);
            }
        }
        $pdf->ln(7);
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
     * Workaround for https://github.com/CatoTH/antragsgruen/issues/137#issuecomment-305686868
     *
     * @param string $text
     * @return string
     */
    public static function stripAfterInsertedNewlines($text)
    {
        return preg_replace_callback('/((<br>\s*)*<\/ins>)(?<rest>.*)(?<end><\/[a-z]+>*)$/siu', function ($match) {
            $rest = $match['rest'];
            if (strpos($rest, '<') !== false) {
                return $match[0];
            } else {
                return '</ins>' . $match['end'];
            }
        }, $text);
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
            $text      = static::stripAfterInsertedNewlines($text);
            $hasInsert = $hasDelete = false;
            if (mb_strpos($text, 'class="inserted"') !== false) {
                $hasInsert = true;
            }
            if (mb_strpos($text, 'class="deleted"') !== false) {
                $hasDelete = true;
            }
            if (preg_match('/<ins( [^>]*)?>/siu', $text)) {
                $hasInsert = true;
            }
            if (preg_match('/<del( [^>]*)?>/siu', $text)) {
                $hasDelete = true;
            }
            $out            .= $wrapStart;
            $out            .= '<h4 class="lineSummary">';
            $isInsertedLine = (mb_strpos($diff['text'], '###LINENUMBER###') === false);
            if ($isInsertedLine) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \Yii::t('diff', 'after_line');
                } elseif ($hasDelete) {
                    $out .= \Yii::t('diff', 'after_line_del');
                } elseif ($hasInsert) {
                    if ($diff['lineTo'] < $firstLineOfSection) {
                        $out .= str_replace('#LINE#', ($diff['lineTo'] + 1), \Yii::t('diff', 'pre_line_ins'));
                    } else {
                        $out .= \Yii::t('diff', 'after_line_ins');
                    }
                }
            } elseif ($diff['lineFrom'] == $diff['lineTo']) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \Yii::t('diff', 'in_line');
                } elseif ($hasDelete) {
                    $out .= \Yii::t('diff', 'in_line_del');
                } elseif ($hasInsert) {
                    $out .= \Yii::t('diff', 'in_line_ins');
                }
            } else {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \yii::t('diff', 'line_to');
                } elseif ($hasDelete) {
                    $out .= \yii::t('diff', 'line_to_del');
                } elseif ($hasInsert) {
                    $out .= \yii::t('diff', 'line_to_ins');
                }
            }
            $lineFrom = ($diff['lineFrom'] < $firstLineOfSection ? $firstLineOfSection : $diff['lineFrom']);
            $out      = str_replace(['#LINETO#', '#LINEFROM#'], [$diff['lineTo'], $lineFrom], $out) . '</h4>';
            $out      .= '<div>';
            if ($text[0] != '<') {
                $out .= '<p>' . $text . '</p>';
            } else {
                $out .= $text;
            }
            $out .= '</div>';
            $out .= $wrapEnd;
        }

        $strSpaceDel   = '<del class="space">[' . \Yii::t('diff', 'space') . ']</del>';
        $strNewlineDel = '<del class="space">[' . \Yii::t('diff', 'newline') . ']</del>';
        $strSpaceIns   = '<ins class="space">[' . \Yii::t('diff', 'space') . ']</ins>';
        $strNewlineIns = '<ins class="space">[' . \Yii::t('diff', 'newline') . ']</ins>';
        $out           = str_replace('<del> </del>', $strSpaceDel, $out);
        $out           = str_replace('<ins> </ins>', $strSpaceIns, $out);
        $out           = str_replace('<del><br></del>', $strNewlineDel . '<del><br></del>', $out);
        $out           = str_replace('<ins><br></ins>', $strNewlineIns . '<ins><br></ins>', $out);
        $out           = str_replace($strSpaceDel . $strNewlineIns, $strNewlineIns, $out);
        $out           = str_replace($strSpaceDel . '<ins></ins><br>', '<br>', $out);
        $out           = str_replace('###LINENUMBER###', '', $out);
        $repl          = '<br></p></div>';
        if (mb_substr($out, mb_strlen($out) - mb_strlen($repl), mb_strlen($repl)) == $repl) {
            $out = mb_substr($out, 0, mb_strlen($out) - mb_strlen($repl)) . '</p></div>';
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
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        if ($this->isEmpty()) {
            return;
        }

        $tex = '';
        /** @var MotionSection $section */
        $section = $this->section;

        $hasLineNumbers = $section->getSettings()->lineNumbers;
        $fixedWidth     = $section->getSettings()->fixedWidth;
        $firstLine      = $section->getFirstLineNumber();

        $title = Exporter::encodePlainString($section->getSettings()->title);
        if ($title == \Yii::t('motion', 'motion_text') && $section->getMotion()->agendaItem) {
            $title = $section->getMotion()->title;
        }
        $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";

        $cacheDeps = [$hasLineNumbers, $firstLine, $fixedWidth, $section->data];
        $tex2      = HashedStaticCache::getCache('printMotionTeX', $cacheDeps);

        if (!$tex2) {
            $tex2 = '';
            if ($fixedWidth || $hasLineNumbers) {
                if ($hasLineNumbers) {
                    $tex2 .= "\\linenumbers\n";
                    $tex2 .= "\\resetlinenumber[" . $firstLine . "]\n";
                }

                $paragraphs = $section->getTextParagraphObjects($hasLineNumbers);
                foreach ($paragraphs as $paragraph) {
                    $tex2 .= Exporter::getMotionLinesToTeX($paragraph->lines) . "\n";
                }

                if ($hasLineNumbers) {
                    if (substr($tex2, -9, 9) == "\\newline\n") {
                        $tex2 = substr($tex2, 0, strlen($tex2) - 9) . "\n";
                    }
                    $tex2 .= "\n\\nolinenumbers\n";
                }
            } else {
                $paras = $section->getTextParagraphLines();
                foreach ($paras as $para) {
                    $html = str_replace('###LINENUMBER###', '', implode('', $para));
                    $tex2 .= Exporter::getMotionLinesToTeX([$html]) . "\n";
                }
            }

            $tex2 = str_replace('\linebreak' . "\n\n", '\linebreak' . "\n", $tex2);
            $tex2 = str_replace('\newline' . "\n\n", '\newline' . "\n", $tex2);
            $tex2 = preg_replace('/\\\\newline\\n{2,}\\\\nolinenumbers/siu', "\n\n\\nolinenumbers", $tex2);

            HashedStaticCache::setCache('printMotionTeX', $cacheDeps, $tex2);
        }

        if ($isRight) {
            $content->textRight .= $tex . $tex2;
        } else {
            $content->textMain .= $tex . $tex2;
        }
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        $cacheDeps = [
            $firstLine, $lineLength, $section->getOriginalMotionSection()->data, $section->data,
            $section->getAmendment()->globalAlternative
        ];
        $tex       = HashedStaticCache::getCache('printAmendmentTeX', $cacheDeps);

        if (!$tex) {
            if ($section->getAmendment()->globalAlternative) {
                $title = Exporter::encodePlainString($section->getSettings()->title);
                if ($title == \Yii::t('motion', 'motion_text')) {
                    $titPattern = \Yii::t('amend', 'amendment_for_prefix');
                    $title      = str_replace('%PREFIX%', $section->getMotion()->titlePrefix, $titPattern);
                }

                $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";
                $tex .= Exporter::encodeHTMLString($section->data);
            } else {
                $formatter = new AmendmentSectionFormatter();
                $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
                $formatter->setTextNew($section->data);
                $formatter->setFirstLineNo($firstLine);
                $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);

                if (count($diffGroups) > 0) {
                    $title = Exporter::encodePlainString($section->getSettings()->title);
                    if ($title == \Yii::t('motion', 'motion_text')) {
                        $titPattern = \Yii::t('amend', 'amendment_for_prefix');
                        $title      = str_replace('%PREFIX%', $section->getMotion()->titlePrefix, $titPattern);
                    }

                    $tex  .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";
                    $html = TextSimple::formatDiffGroup($diffGroups, '', '<br><br>');
                    $tex  .= Exporter::encodeHTMLString($html);
                }
            }

            HashedStaticCache::setCache('printAmendmentTeX', $cacheDeps, $tex);
        }

        if ($isRight) {
            $content->textRight .= $tex;
        } else {
            $content->textMain .= $tex;
        }
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
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $section->data;
        }

        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

        $diff = static::formatDiffGroup($diffGroups);
        $diff = str_replace('<h4', '<br><h4', $diff);
        $diff = str_replace('</h4>', '</h4><br>', $diff);
        if (mb_substr($diff, 0, 4) == '<br>') {
            $diff = mb_substr($diff, 4);
        }
        return $diff;
    }

    /**
     * @param Text $odt
     * @return void
     */
    public function printMotionToODT(Text $odt)
    {
        if ($this->isEmpty()) {
            return;
        }
        $section = $this->section;
        /** @var MotionSection $section */
        $lineNumbers = $section->getMotion()->getMyConsultation()->getSettings()->odtExportHasLineNumers;
        $odt->addHtmlTextBlock('<h2>' . Html::encode($section->getSettings()->title) . '</h2>', false);
        if ($section->getSettings()->lineNumbers && $lineNumbers) {
            $paragraphs = $section->getTextParagraphObjects(true, false, false);
            foreach ($paragraphs as $paragraph) {
                $lines = [];
                foreach ($paragraph->lines as $line) {
                    $lines[] = preg_replace('/<br> ?\n?$/', '', $line);
                }
                $html = implode('<br>', $lines);
                $html = str_replace('###LINENUMBER###', '', $html);
                if (mb_substr($html, 0, 1) != '<') {
                    $html = '<p>' . $html . '</p>';
                }

                $html = str_replace('<br><ul>', '<ul>', $html);
                $html = str_replace('<br><ol>', '<ol>', $html);
                $html = str_replace('<br><li>', '<li>', $html);

                $html = HTMLTools::correctHtmlErrors($html);
                $odt->addHtmlTextBlock($html, true);
            }
        } else {
            $paras = $section->getTextParagraphLines();
            foreach ($paras as $para) {
                $html = str_replace('###LINENUMBER###', '', implode('', $para));
                $html = HTMLTools::correctHtmlErrors($html);
                $odt->addHtmlTextBlock($html, false);
            }
        }
    }

    /**
     * @param Text $odt
     * @return void
     */
    public function printAmendmentToODT(Text $odt)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);

            $html = HTMLTools::correctHtmlErrors($section->data);
            $odt->addHtmlTextBlock($html, false);
        } else {
            $firstLine  = $section->getFirstLineNumber();
            $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->data);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

            if (count($diffGroups) == 0) {
                return;
            }

            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);

            $firstLine = $section->getFirstLineNumber();
            $html      = TextSimple::formatDiffGroup($diffGroups, '', '', $firstLine);

            $html = HTMLTools::correctHtmlErrors($html);
            $odt->addHtmlTextBlock($html, false);
        }
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


    private static $CHANGESET_COUNTER = 0;

    /**
     * @param int[] $toMergeAmendmentIds
     * @param array $changeset
     * @return string
     */
    public function getMotionTextWithInlineAmendments($toMergeAmendmentIds, &$changeset)
    {
        /** @var MotionSection $section */
        $section    = $this->section;
        $merger     = $section->getAmendmentDiffMerger($toMergeAmendmentIds);
        $paragraphs = $section->getTextParagraphObjects(false, false, false);

        /** @var Amendment[] $amendmentsById */
        $amendmentsById = [];
        foreach ($section->amendingSections as $sect) {
            $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
        }

        $paragraphCollissions = [];
        foreach (array_keys($paragraphs) as $paragraphNo) {
            $paragraphCollissions[$paragraphNo] = $merger->getCollidingParagraphGroups($paragraphNo, 10);
        }

        $out = '';
        foreach (array_keys($paragraphs) as $paragraphNo) {
            $groupedParaData = $merger->getGroupedParagraphData($paragraphNo);
            $paragraphText   = '';
            foreach ($groupedParaData as $part) {
                $text = $part['text'];

                if ($part['amendment'] > 0) {
                    $amendment = $amendmentsById[$part['amendment']];
                    $cid       = static::$CHANGESET_COUNTER++;
                    if (!isset($changeset[$amendment->id])) {
                        $changeset[$amendment->id] = [];
                    }
                    $changeset[$amendment->id][] = $cid;

                    $mid  = $cid . '-' . $amendment->id;
                    $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                    $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
                }

                $paragraphText .= $text;
            }

            $out .= '<div class="paragraphHolder';
            if (count($paragraphCollissions[$paragraphNo]) > 0) {
                $out .= ' hasCollissions';
            }
            $out .= '" data-paragraph-no="' . $paragraphNo . '">';
            $out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);

            foreach ($paragraphCollissions[$paragraphNo] as $amendmentId => $paraData) {
                $amendment     = $amendmentsById[$amendmentId];
                $out           .= '<div class="collidingParagraph"';
                $out           .= ' data-link="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '"';
                $out           .= ' data-username="' . Html::encode($amendment->getInitiatorsStr()) . '">';
                $out           .= '<p class="collidingParagraphHead"><strong>' . \Yii::t('amend', 'merge_colliding');
                $out           .= ': ' . Html::a($amendment->titlePrefix, UrlHelper::createAmendmentUrl($amendment));
                $out           .= '</strong></p>';
                $paragraphText = '';

                foreach ($paraData as $part) {
                    $text = $part['text'];

                    if ($part['amendment'] > 0) {
                        $amendment = $amendmentsById[$part['amendment']];
                        $cid       = static::$CHANGESET_COUNTER++;
                        if (!isset($changeset[$amendment->id])) {
                            $changeset[$amendment->id] = [];
                        }
                        $changeset[$amendment->id][] = $cid;

                        $mid  = $cid . '-' . $amendment->id;
                        $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                        $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
                    }

                    $paragraphText .= $text;
                }

                $out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
                $out .= '</div>';
            }
            $out .= '</div>';
        }

        return $out;
    }
}
