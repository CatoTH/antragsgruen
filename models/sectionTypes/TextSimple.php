<?php

namespace app\models\sectionTypes;

use app\components\diff\{AmendmentSectionFormatter, Diff, DiffRenderer};
use app\models\SectionedParagraph;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use CatoTH\HTML2OpenDocument\Text as ODTText;
use app\components\{HashedStaticCache, html2pdf\Content as HtmlToPdfContent, HTMLTools, LineSplitter, RequestContext};
use app\models\db\{AmendmentSection, Consultation, ConsultationMotionType, MotionSection};
use app\models\forms\CommentForm;
use yii\helpers\Html;
use yii\web\View;

class TextSimple extends TextSimpleCommon
{
    private bool $forceMultipleParagraphs = false;

    public function forceMultipleParagraphMode(bool $active): void
    {
        $this->forceMultipleParagraphs = $active;
    }

    public function getMotionFormField(): string
    {
        return $this->getTextMotionFormField(false, !!$this->section->getSettings()->fixedWidth);
    }

    public function getAmendmentFormField(): string
    {
        $this->section->getSettings()->maxLen = 0; // @TODO Dirty Hack
        $fixedWidth = !!$this->section->getSettings()->fixedWidth;

        $multipleParagraphs = $this->section->getSettings()->motionType->amendmentMultipleParagraphs;
        if ($this->forceMultipleParagraphs) {
            $multipleParagraphs = ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE;
        }

        if ($multipleParagraphs === ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE) {
            /** @var AmendmentSection $section */
            $section = $this->section;

            if ($section->getAmendment() && $section->getAmendment()->globalAlternative) {
                $amendmentHtml = $section->data;
            } else {
                $diff = new Diff();
                if ($section->getOriginalMotionSection()) {
                    $origParas = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->getData());
                } else {
                    $origParas = [];
                }
                $amendParas = HTMLTools::sectionSimpleHTML($section->data);
                $amDiffSections = $diff->compareHtmlParagraphs($origParas, $amendParas, DiffRenderer::FORMATTING_ICE);
                $amendmentHtml = implode('', $amDiffSections);
            }

            return $this->getTextAmendmentFormField(false, $amendmentHtml, $fixedWidth);
        } else {
            // On server side, we do not make a difference between single paragraph and single change mode, as this is enforced on client-side only
            $singleChange = ($multipleParagraphs === ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_CHANGE);
            return $this->getTextAmendmentFormFieldSingleParagraph($fixedWidth, $singleChange);
        }
    }

    public function getTextAmendmentFormFieldSingleParagraph(bool $fixedWidth, bool $singleChange): string
    {
        /** @var AmendmentSection $amSection */
        $amSection = $this->section;
        if ($amSection->getOriginalMotionSection()) {
            $origParas = HTMLTools::sectionSimpleHTML($amSection->getOriginalMotionSection()->getData());
        } else {
            $origParas = [];
        }
        $amParas = array_map(fn(SectionedParagraph $par) => $par->html, $origParas);

        $changedParagraph = -1;
        foreach ($amSection->diffStrToOrigParagraphs($origParas, true, DiffRenderer::FORMATTING_ICE) as $paraNo => $para) {
            $amParas[$paraNo] = $para;
            $changedParagraph = $paraNo;
        }

        $type = $this->section->getSettings();
        $str  = '<div class="label">' . Html::encode($this->getTitle()) . '</div>';
        $str  .= '<div class="texteditorBox" data-section-id="' . $amSection->sectionId . '" ' .
            'data-changed-para-no="' . $changedParagraph . '">';
        foreach ($origParas as $paraNo => $moPara) {
            $nameBase = 'sections[' . $type->id . '][' . $paraNo . ']';
            $htmlId   = 'sections_' . $type->id . '_' . $paraNo;
            $holderId = 'section_holder_' . $type->id . '_' . $paraNo;

            $str .= '<div class="form-group wysiwyg-textarea single-paragraph" id="' . $holderId . '"';
            $str .= ' data-max-len="' . $type->maxLen . '" data-full-html="0"';
            $str .= ' data-original="' . Html::encode($moPara->html) . '"';
            $str .= ' data-paragraph-no="' . $paraNo . '"';
            $str .= ' dir="' . ($this->section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '"';
            $str .= '><label for="' . $htmlId . '" class="hidden">' . Html::encode($this->getTitle()) . '</label>';

            $str .= '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
                'title="' . Html::encode($this->getTitle()) . '"></textarea>';
            $str .= '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
                'title="' . Html::encode($this->getTitle()) . '"></textarea>';
            $str .= '<div class="texteditor motionTextFormattings';
            if ($fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '" dir="' . ($this->section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '" ' .
                'data-track-changed="1" data-enter-mode="br" data-no-strike="1" id="' . $htmlId . '_wysiwyg" ' .
                'title="' . Html::encode($this->getTitle()) . '">';
            $str .= $amParas[$paraNo];
            $str .= '</div>';

            $str .= '<div class="oneChangeHint hidden"><div class="alert alert-danger"><p>';
            $str .= \Yii::t('amend', 'err_one_change');
            $str .= '</p></div></div>';

            $str .= '<div class="modifiedActions"><button type="button" class="btn btn-link revert">';
            $str .= \Yii::t('amend', 'revert_changes');
            $str .= '</button></div>';

            $str .= '</div>';
        }
        $str .= '</div>';

        return $str;
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;
        $settings = $section->getSettings();

        $html = '<section class="motionSection">';

        $paragraphs = $section->getTextParagraphObjects(!!$section->getSettings()->lineNumbers);
        $lineNo = $section->getFirstLineNumber();

        foreach ($paragraphs as $i => $paragraph) {
            $html .= '<div class="text motionTextFormattings textOrig';
            if ($section->getSettings()->fixedWidth) {
                $html .= ' fixedWidthFont';
            }
            if ($i === 0 && $settings->printTitle) {
                $html .= ' paragraphWithHeader';
            }
            $html .= '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';

            if ($i === 0 && $settings->printTitle) {
                $html .= '<h2>' . Html::encode($this->getTitle()) . "</h2>\n";
            }

            if ($section->getSettings()->fixedWidth || $section->getSettings()->lineNumbers) {
                foreach ($paragraph->lines as $j => $line) {
                    if ($section->getSettings()->lineNumbers) {
                        $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '" aria-hidden="true"></span>';
                        $line = str_replace('###LINENUMBER###', $lineNoStr, $line);
                    } else {
                        $line = str_replace('###LINENUMBER###', '', $line);
                    }
                    $line = str_replace('<br>', '', $line);
                    $first3 = substr($line, 0, 3);
                    if ($j > 0 && !in_array($first3, ['<ol', '<ul', '<p>', '<di'])) {
                        $html .= '<br>';
                    }
                    $html .= $line;
                }
            } else {
                $html .= $paragraph->origStr;
            }
            $html .= '</div>';
        }

        $html .= '</section>';

        if ($section->isLayoutRight()) {
            $content->textRight .= $html;
        } else {
            $content->textMain .= $html;
        }
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        $title = $this->getTitle();
        if ($title == \Yii::t('motion', 'motion_text')) {
            $titPattern = \Yii::t('amend', 'amendment_for_prefix');
            $title = str_replace('%PREFIX%', $section->getMotion()->getFormattedTitlePrefix(), $titPattern);
        }
        $str = '<h2 class="green">' . Html::encode($title) . '</h2>';

        if ($section->getAmendment()->globalAlternative) {
            $str .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';

            $htmlSections = HTMLTools::sectionSimpleHTML($section->data);
            foreach ($htmlSections as $htmlSection) {
                $str .= '<div class="paragraph"><div class="text motionTextFormattings';
                if ($this->section->getSettings()->fixedWidth) {
                    $str .= ' fixedWidthFont';
                }
                $str .= '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">' . $htmlSection->html . '</div></div>';
            }

            $str .= '</div>';
            $content->textMain .= $str;

            return;
        }

        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;
        $firstLine  = $section->getFirstLineNumber();

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($firstLine);

        $wrapStart = '<section class="paragraph"><div class="text motionTextFormattings';
        if ($section->getSettings()->fixedWidth) {
            $wrapStart .= ' fixedWidthFont';
        }
        $wrapStart .= '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';
        $wrapEnd   = '</div></section>';

        if ($this->defaultOnlyDiff) {
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);
            if (count($diffGroups) === 0) {
                return;
            }

            $str .= '<div class="paragraph lineNumbers">';
            $str .= TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine, null);
            $str .= '</div>';
        } else {
            $str .= '<div class="paragraph lineNumbers">';

            $diffs = $formatter->getDiffSectionsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

            $lineNo = $firstLine;
            foreach ($diffs as $diffSection) {
                $lineNumbers = substr_count($diffSection, '###LINENUMBER###');
                $html = LineSplitter::replaceLinebreakPlaceholdersByMarkup($diffSection, !!$section->getSettings()->lineNumbers, $lineNo);
                $lineNo += $lineNumbers;
                $str .= $wrapStart . $html . $wrapEnd;
            }

            $str .= '</div>';
        }

        $content->textMain .= $str;
    }

    /**
     * @param string $data
     */
    public function setMotionData($data, bool $allowForbidden = false): void
    {
        $type = $this->section->getSettings();
        $forbiddenFormattings = ($allowForbidden ? [] : $type->getForbiddenMotionFormattings());
        $this->section->dataRaw = $data;
        $this->section->setData(HTMLTools::cleanSimpleHtml($data, $forbiddenFormattings));
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->dataRaw = null;
    }

    /**
     * @param array $data
     * @throws \app\models\exceptions\Internal
     */
    public function setAmendmentData($data): void
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        $post    = RequestContext::getWebRequest()->post();

        $multipleParagraphs = $this->section->getSettings()->motionType->amendmentMultipleParagraphs;
        if ($this->forceMultipleParagraphs) {
            $multipleParagraphs = ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE;
        }

        if ($multipleParagraphs === ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE) {
            $section->data    = HTMLTools::stripEmptyBlockParagraphs(HTMLTools::cleanSimpleHtml($data['consolidated']));
            $section->dataRaw = $data['raw'];
        } else {
            // On server side, we do not make a difference between single paragraph and single change mode, as this is enforced on client-side only
            $moSection = $section->getOriginalMotionSection();
            $paras = HTMLTools::sectionSimpleHTML($moSection->getData(), true);
            $paras = array_map(fn(SectionedParagraph $par) => $par->html, $paras);

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

    public function getMotionPlainHtml(): string
    {
        $html = $this->section->getData();
        $html = str_replace('<span class="underline">', '<span style="text-decoration: underline;">', $html);
        $html = str_replace('<span class="strike">', '<span style="text-decoration: line-through;">', $html);
        return $html;
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var MotionSection $section */
        $section = $this->section;

        if ($section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $pdf->printMotionSection($section);
    }

    public function getMotionPlainHtmlWithLineNumbers(): string
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $paragraphBegin = '<div class="text motionTextFormattings textOrig';
        if ($section->getSettings()->fixedWidth) {
            $paragraphBegin .= ' fixedWidthFont';
        }
        $paragraphBegin .= '">';
        $paragraphEnd = '</div>';

        if ($this->isEmpty()) {
            return '';
        }
        if (!$section->getSettings()->lineNumbers) {
            return $paragraphBegin . $this->getMotionPlainHtml() . $paragraphEnd;
        }

        $lineNo    = $section->getFirstLineNumber();
        $paragraphs     = $section->getTextParagraphObjects(true, true, true);
        $str = '';
        foreach ($paragraphs as $paragraph) {
            $str .= $paragraphBegin;
            foreach ($paragraph->lines as $i => $line) {
                $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '" aria-hidden="true"></span>';
                $line = str_replace('###LINENUMBER###', $lineNoStr, $line);
                $line = str_replace('<br>', '', $line);
                $first3 = substr($line, 0, 3);
                if ($i > 0 && !in_array($first3, ['<ol', '<ul', '<p>', '<di'])) {
                    $str .= '<br>';
                }
                $str .= $line;
            }
            $str .= $paragraphEnd;
        }

        return $str;
    }

    private function getAmendmentPlainHtmlCalcText(AmendmentSection $section, int $firstLine, int $lineLength): string
    {
        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);
        if (count($diffGroups) === 0) {
            return '';
        }

        return TextSimple::formatDiffGroup($diffGroups, '', '', $firstLine);
    }

    public function getAmendmentPlainHtml(bool $skipTitle = false): string
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        if ($section->getAmendment()->globalAlternative) {
            $str = ($skipTitle ? '' : '<h2>' . Html::encode($this->getTitle()) . '</h2>');
            $str .= '<p><strong>' . \Yii::t('amend', 'global_alternative') . '</strong></p>';
            $str .= $section->data;
            return $str;
        } elseif ($skipTitle) {
            return $this->getAmendmentPlainHtmlCalcText($section, $firstLine, $lineLength);
        } else {
            $cacheDeps = [$section->getOriginalMotionSection()->getData(), $section->data, $firstLine, $lineLength, $this->getTitle()];
            $cache = HashedStaticCache::getInstance('getAmendmentPlainHtml', $cacheDeps);
            return $cache->getCached(function () use ($section, $firstLine, $lineLength) {
                $text = $this->getAmendmentPlainHtmlCalcText($section, $firstLine, $lineLength);
                if ($text !== '') {
                    $text = '<h2>' . Html::encode($this->getTitle()) . '</h2>' . $text;
                }
                return $text;
            });
        }
    }

    /**
     * @param int[] $openedComments
     */
    public function showMotionView(?CommentForm  $commentForm, array $openedComments): string
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

    public function getAmendmentUnchangedVersionODS(): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $section->data;
        }

        $firstLine    = $section->getFirstLineNumber();
        $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
        $originalData = $section->getOriginalMotionSection()->getData();

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($originalData);
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

        $unchanged = [];
        foreach ($diffGroups as $diffGroup) {
            $lineFrom = ($diffGroup->lineFrom > 0 ? $diffGroup->lineFrom : 1);
            $unchanged[] = LineSplitter::extractLines($originalData, $lineLength, $firstLine, $lineFrom, $diffGroup->lineTo);
        }

        return implode('<br><br>', $unchanged);
    }

    public function printMotionToODT(ODTText $odt): void
    {
        if ($this->isEmpty()) {
            return;
        }
        $section = $this->section;
        /** @var MotionSection $section */
        $lineNumbers = $section->getMotion()->getMyConsultation()->getSettings()->odtExportHasLineNumers;
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
        if ($section->getSettings()->lineNumbers && $lineNumbers) {
            $paragraphs = $section->getTextParagraphObjects(true, false, false);
            foreach ($paragraphs as $paragraph) {
                $lines = [];
                foreach ($paragraph->lines as $line) {
                    $lines[] = preg_replace('/<br> ?\n?$/', '', $line);
                }
                $html = implode('<br>', $lines);
                $html = str_replace('###LINENUMBER###', '', $html);
                if (grapheme_substr($html, 0, 1) != '<') {
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
                $html = str_replace('###LINENUMBER###', '', implode('', $para->lines));
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
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);

            $html = HTMLTools::correctHtmlErrors($section->data);
            $odt->addHtmlTextBlock($html, false);
        } else {
            $firstLine  = $section->getFirstLineNumber();
            $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);

            if ($this->defaultOnlyDiff) {
                $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

                if (count($diffGroups) === 0) {
                    return;
                }

                $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);

                $firstLine = $section->getFirstLineNumber();
                $html = TextSimple::formatDiffGroup($diffGroups, '', '', $firstLine);
            } else {
                $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);

                $diffs = $formatter->getDiffSectionsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);
                $html = '';
                $lineNo = $firstLine;
                foreach ($diffs as $diffSection) {
                    $lineNumbers = substr_count($diffSection, '###LINENUMBER###');
                    $html .= LineSplitter::replaceLinebreakPlaceholdersByMarkup($diffSection, !!$section->getSettings()->lineNumbers, $lineNo);
                    $lineNo += $lineNumbers;
                }
            }

            $html = HTMLTools::correctHtmlErrors($html);
            $odt->addHtmlTextBlock($html, false);

        }
    }

    public function showIfEmpty(): bool
    {
        return false;
    }
}
