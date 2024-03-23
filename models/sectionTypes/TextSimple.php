<?php

namespace app\models\sectionTypes;

use app\components\diff\{AmendmentSectionFormatter, Diff, DiffRenderer};
use app\models\SectionedParagraph;
use app\components\{HashedStaticCache, HTMLTools, LineSplitter, RequestContext};
use app\models\db\{AmendmentSection, ConsultationMotionType, MotionSection};
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
        $moSection = $amSection->getOriginalMotionSection();
        $moParas = HTMLTools::sectionSimpleHTML($moSection->getData(), false);
        $amParas = array_map(fn(SectionedParagraph $par) => $par->html, $moParas);

        $changedParagraph = -1;
        foreach ($amSection->diffStrToOrigParagraphs($moParas, false, DiffRenderer::FORMATTING_ICE) as $paraNo => $para) {
            $amParas[$paraNo] = $para;
            $changedParagraph = $paraNo;
        }

        $type = $this->section->getSettings();
        $str  = '<div class="label">' . Html::encode($this->getTitle()) . '</div>';
        $str  .= '<div class="texteditorBox" data-section-id="' . $amSection->sectionId . '" ' .
            'data-changed-para-no="' . $changedParagraph . '">';
        foreach ($moParas as $paraNo => $moPara) {
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
            $paras = HTMLTools::sectionSimpleHTML($moSection->getData(), false);
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
            $str = ($skipTitle ? '' : '<h3>' . Html::encode($this->getTitle()) . '</h3>');
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
                    $text = '<h3>' . Html::encode($this->getTitle()) . '</h3>' . $text;
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
}
