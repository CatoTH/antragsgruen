<?php

namespace app\models\sectionTypes;

use app\components\diff\{AmendmentSectionFormatter, Diff, DiffRenderer};
use app\components\HashedStaticCache;
use app\components\HTMLTools;
use app\components\latex\{Content, Exporter};
use app\models\db\{AmendmentSection, Consultation, MotionSection};
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;
use yii\web\View;
use CatoTH\HTML2OpenDocument\Text as ODTText;

class TextSimple extends Text
{
    private $forceMultipleParagraphs = null;

    /**
     * @param bool $active
     */
    public function forceMultipleParagraphMode($active)
    {
        $this->forceMultipleParagraphs = $active;
    }

    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(false, $this->section->getSettings()->fixedWidth);
    }

    public function getAmendmentFormField(): string
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth                           = $this->section->getSettings()->fixedWidth;

        $multipleParagraphs = $this->section->getSettings()->motionType->amendmentMultipleParagraphs;
        if ($this->forceMultipleParagraphs !== null) {
            $multipleParagraphs = $this->forceMultipleParagraphs;
        }

        if ($multipleParagraphs) {
            /** @var AmendmentSection $section */
            $section = $this->section;
            $diff    = new Diff();
            if ($section->getOriginalMotionSection()) {
                $origParas = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->data);
            } else {
                $origParas = [];
            }
            $amendParas     = HTMLTools::sectionSimpleHTML($section->data);
            $amDiffSections = $diff->compareHtmlParagraphs($origParas, $amendParas, DiffRenderer::FORMATTING_ICE);
            $amendmentHtml  = implode('', $amDiffSections);

            return $this->getTextAmendmentFormField(false, $amendmentHtml, $fixedWidth);
        } else {
            return $this->getTextAmendmentFormFieldSingleParagraph($fixedWidth);
        }
    }

    /**
     * @param bool $fixedWidth
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public function getTextAmendmentFormFieldSingleParagraph($fixedWidth)
    {
        /** @var AmendmentSection $amSection */
        $amSection = $this->section;
        $moSection = $amSection->getOriginalMotionSection();
        $moParas   = HTMLTools::sectionSimpleHTML($moSection->data, false);

        $amParas          = $moParas;
        $changedParagraph = -1;
        foreach ($amSection->diffStrToOrigParagraphs($moParas, false) as $paraNo => $para) {
            $amParas[$paraNo] = $para;
            $changedParagraph = $paraNo;
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
            $str .= '<div class="texteditor motionTextFormattings';
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
     */
    public function setMotionData($data)
    {
        $type                   = $this->section->getSettings();
        $this->section->dataRaw = $data;
        $this->section->data    = HTMLTools::cleanSimpleHtml($data, $type->getForbiddenMotionFormattings());
    }

    public function deleteMotionData()
    {
        $this->section->data    = null;
        $this->section->dataRaw = null;
    }

    /**
     * @param array $data
     * @throws \app\models\exceptions\Internal
     */
    public function setAmendmentData($data)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        $post    = \Yii::$app->request->post();

        $multipleParagraphs = $this->section->getSettings()->motionType->amendmentMultipleParagraphs;
        if ($this->forceMultipleParagraphs !== null) {
            $multipleParagraphs = $this->forceMultipleParagraphs;
        }

        if ($multipleParagraphs) {
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

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="paragraph"><div class="text motionTextFormattings';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '">' . $section . '</div></div>';
        }
        return $str;
    }

    public function getMotionPlainHtml(): string
    {
        $html = $this->section->data;
        $html = str_replace('<span class="underline">', '<span style="text-decoration: underline;">', $html);
        $html = str_replace('<span class="strike">', '<span style="text-decoration: line-through;">', $html);
        return $html;
    }

    public function getAmendmentPlainHtml(): string
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        if ($section->getAmendment()->globalAlternative) {
            $str = '<h3>' . Html::encode($section->getSettings()->title) . '</h3>';
            $str .= '<p><strong>' . \Yii::t('amend', 'global_alternative') . '</strong></p>';
            $str .= $section->data;
            return $str;
        } else {
            $cacheDeps = [$section->getOriginalMotionSection()->data, $section->data, $firstLine, $lineLength, $section->getSettings()->title];
            $cached    = HashedStaticCache::getCache('getAmendmentPlainHtml', $cacheDeps);
            if (!$cached) {
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
                $cached = $str;
                HashedStaticCache::setCache('getAmendmentPlainHtml', $cacheDeps, $cached);
            }
            return $cached;
        }
    }

    /**
     * @return string
     * @throws \app\models\exceptions\Internal
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
            $str .= '<div class="paragraph"><div class="text motionTextFormattings';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '">' . $htmlSection . '</div></div>';
        }

        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
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

        if ($sectionTitlePrefix) {
            $sectionTitlePrefix .= ': ';
        }
        $title     = $sectionTitlePrefix . $section->getSettings()->title;
        $str       = '<div id="section_' . $section->sectionId . '" class="motionTextHolder">';
        $str       .= '<h3 class="green">' . Html::encode($title) . '</h3>';
        $str       .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
        $wrapStart = '<section class="paragraph"><div class="text motionTextFormattings';
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
     * This adds <br>-tags where necessary.
     * Test cases are collected in the "Listen-Test"-motion.
     * Check in the TCPDF-generated PDF that line numbers match the lines.
     *
     * @param string[] $linesArr
     * @return string[]
     */
    private function printMotionToPDFAddLinebreaks($linesArr)
    {
        for ($i = 1; $i < count($linesArr); $i++) {
            // Does this line start with an ol/ul/li?
            if (!preg_match('/^<(ol|ul|li)/siu', $linesArr[$i])) {
                continue;
            }
            // Does the previous line end a block element? If not, we need the extra BR
            if (!preg_match('/<\/(div|p|blockquote|ul|ol|h1|h2|h3|h4|h5|h6)>$/siu', $linesArr[$i - 1])) {
                $linesArr[$i] = '<br>' . $linesArr[$i];
            }
        }

        return $linesArr;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;

        if ($section->getSettings()->printTitle) {
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

                // Hint about <li>s: The spacing between list items is created by </li><br><li>-markup.
                // This obviously is incorrect according to HTML, but is rendered correctly neverless.
                // We just have to take care about additional spacing for the line numbers in these cases.

                if ($hasLineNumbers) {
                    $lineNos = [];
                    for ($i = 0; $i < count($paragraph->lines); $i++) {
                        if (preg_match('/^<(ul|ol|li)/siu', $linesArr[$i])) {
                            $lineNos[] = ''; // Just for having an additional <br>
                        }
                        $lineNos[] = $linenr++;
                    }
                    $text2 = implode('<br>', $lineNos);
                } else {
                    $text2 = '';
                }

                $y = $pdf->getY();
                $pdf->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);
                $linesArr = $this->printMotionToPDFAddLinebreaks($linesArr);
                $text1    = implode('<br>', $linesArr);

                // instead of <span class="strike"></span> TCPDF can only handle <s></s>
                // for striking through text
                $text1   = preg_replace('/<span class="strike">(.*)<\/span>/iUs', '<s>${1}</s>', $text1);

                // instead of <span class="underline"></span> TCPDF can only handle <u></u>
                // for underlined text
                $text1   = preg_replace('/<span class="underline">(.*)<\/span>/iUs', '<u>${1}</u>', $text1);

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

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->getAmendment()->globalAlternative) {
            if ($section->getSettings()->printTitle) {
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
                if ($section->getSettings()->printTitle) {
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
     * @param CommentForm|null $commentForm
     * @param int[] $openedComments
     * @return string
     */
    public function showMotionView($commentForm, $openedComments)
    {
        return (new View())->render(
            '@app/views/motion/showSimpleTextSection',
            [
                'section'        => $this->section,
                'openedComments' => $openedComments,
                'commentForm'    => $commentForm,
            ],
            \Yii::$app->controller
        );
    }

    public function isEmpty(): bool
    {
        return ($this->section->data === '');
    }

    /**
     * Workaround for https://github.com/CatoTH/antragsgruen/issues/137#issuecomment-305686868
     *
     * @param string $text
     * @return string
     */
    public static function stripAfterInsertedNewlines($text)
    {
        return preg_replace_callback('/((<br>\s*)+<\/ins>)(?<rest>.*)(?<end><\/[a-z]+>*)$/siu', function ($match) {
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

    public function getMotionPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    public function getAmendmentPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->data);
    }

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $tex = '';
        /** @var MotionSection $section */
        $section  = $this->section;
        $settings = $section->getSettings();

        $hasLineNumbers = $settings->lineNumbers;
        $lineLength     = ($hasLineNumbers ? $consultation->getSettings()->lineLength : 0);
        $fixedWidth     = $settings->fixedWidth;
        $firstLine      = $section->getFirstLineNumber();

        if ($settings->printTitle) {
            $title = Exporter::encodePlainString($settings->title);
            /*
            if ($title == \Yii::t('motion', 'motion_text') && $section->getMotion()->agendaItem) {
                $title = $section->getMotion()->title;
            }
            */
            $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";
        }

        $cacheDeps = [$hasLineNumbers, $lineLength, $firstLine, $fixedWidth, $section->data];
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

    public function printAmendmentTeX(bool $isRight, Content $content): void
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
                    $html = TextSimple::formatDiffGroup($diffGroups, '', '<p></p>');

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

    public function getMotionODS(): string
    {
        return $this->section->data;
    }

    public function getAmendmentODS(): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $section->data;
        }

        $firstLine    = $section->getFirstLineNumber();
        $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
        $originalData = $section->getOriginalMotionSection()->data;
        return static::formatAmendmentForOds($originalData, $section->data, $firstLine, $lineLength);
    }

    public function printMotionToODT(ODTText $odt): void
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

    public function printAmendmentToODT(ODTText $odt): void
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

    /**
     * @param string $originalText
     * @param string $newText
     * @param int $firstLine
     * @param int $lineLength
     * @return string
     * @throws \app\models\exceptions\Internal
     */
    public static function formatAmendmentForOds($originalText, $newText, $firstLine, $lineLength)
    {
        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($originalText);
        $formatter->setTextNew($newText);
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
}
