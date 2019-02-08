<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use yii\helpers\Html;

abstract class Text extends ISectionType
{
    /**
     * @param bool $fullHtml
     * @param bool $fixedWidth
     * @return string
     */
    protected function getTextMotionFormField($fullHtml, $fixedWidth)
    {
        $type   = $this->section->getSettings();
        $formId = $type->id;
        $htmlId = 'sections_' . $formId;

        $str = '<div class="form-group wysiwyg-textarea" id="section_holder_' . $formId . '"';
        $str .= ' data-max-len="' . IntVal($type->maxLen) . '"';
        $str .= ' data-full-html="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '>';
        $str .= $this->getFormLabel();

        if ($type->maxLen != 0) {
            $len = abs($type->maxLen);
            $str .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign"></span> ';
            $str .= str_replace(
                ['%LEN%', '%COUNT%'],
                [$len, '<span class="counter"></span>'],
                \Yii::t('motion', 'max_len_hint')
            );
            $str .= '</div>';
        }

        $str .= '<textarea name="sections[' . $formId . ']"  id="sections_' . $formId . '" ';
        $str .= 'title="' . Html::encode($type->title) . '">';
        $str .= Html::encode($this->section->data) . '</textarea>';
        $str .= '<div class="texteditor motionTextFormattings boxed';
        if ($fixedWidth) {
            $str .= ' fixedWidthFont';
        }
        $str .= '" id="' . $htmlId . '_wysiwyg" ';
        if (in_array('strike', $type->getForbiddenMotionFormattings())) {
            $str .= 'data-no-strike="1" ';
        }
        $str .= 'title="' . Html::encode($type->title) . '">';
        $str .= $this->section->data;
        $str .= '</div>';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert"></span> ' . \Yii::t('motion', 'max_len_alert');
            $str .= '</div>';
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @param bool $fullHtml
     * @param string $data
     * @param bool $fixedWidth
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getTextAmendmentFormField($fullHtml, $data, $fixedWidth)
    {
        /** @var AmendmentSection $section */
        $section      = $this->section;
        $type         = $section->getSettings();
        $nameBase     = 'sections[' . $type->id . ']';
        $htmlId       = 'sections_' . $type->id;
        $originalHtml = ($section->getOriginalMotionSection() ? $section->getOriginalMotionSection()->data : '');

        $str = '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-max-len="' . $type->maxLen . '"';
        $str .= ' data-full-html="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        $str .= '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        $str .= '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        $str .= '<div class="motionTextFormatted motionTextFormattings texteditor boxed';
        if ($fixedWidth) {
            $str .= ' fixedWidthFont';
        }
        $str .= '" data-track-changed="1" data-enter-mode="br" data-no-strike="1" ' .
            'data-original-html="' . Html::encode($originalHtml) . '" ' .
            'id="' . $htmlId . '_wysiwyg" title="' . Html::encode($type->title) . '">';
        $str .= HTMLTools::prepareHTMLForCkeditor($data);
        $str .= '</div>';

        if (HTMLTools::cleanSimpleHtml($originalHtml) !== HTMLTools::cleanSimpleHtml($data)) {
            $str .= '<div class="modifiedActions"><button class="btn-link resetText" type="button">';
            $str .= \Yii::t('amend', 'revert_changes');
            $str .= '</button></div>';
        }

        $str .= '</div>';

        return $str;
    }
}
