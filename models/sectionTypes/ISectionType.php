<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\controllers\Base;
use app\models\db\AmendmentSection;
use app\models\db\IMotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use CatoTH\HTML2OpenDocument\Text;
use yii\helpers\Html;

abstract class ISectionType
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;
    const TYPE_TABULAR     = 4;
    const TYPE_PDF         = 5;

    /** @var IMotionSection */
    protected $section;

    /**
     * @param IMotionSection $section
     */
    public function __construct(IMotionSection $section)
    {
        $this->section = $section;
    }

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return [
            static::TYPE_TITLE       => \yii::t('structure', 'section_title'),
            static::TYPE_TEXT_SIMPLE => \yii::t('structure', 'section_text'),
            static::TYPE_TEXT_HTML   => \yii::t('structure', 'section_html'),
            static::TYPE_IMAGE       => \yii::t('structure', 'section_image'),
            static::TYPE_TABULAR     => \yii::t('structure', 'section_tabular'),
            static::TYPE_PDF         => \yii::t('structure', 'section_pdf'),
        ];
    }

    /**
     * @param bool $fullHtml
     * @param bool $fixedWidth
     * @return string
     */
    protected function getTextMotionFormField($fullHtml, $fixedWidth)
    {
        $type   = $this->section->getSettings();
        $htmlId = 'sections_' . $type->id;

        $str = '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-max-len="' . $type->maxLen . '"';
        $str .= ' data-full-html="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

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

        $str .= '<textarea name="sections[' . $type->id . ']"  id="sections_' . $type->id . '" ';
        $str .= 'title="' . Html::encode($type->title) . '">';
        $str .= Html::encode($this->section->data) . '</textarea>';
        $str .= '<div class="texteditor boxed';
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
        $str .= '<div class="motionTextFormatted texteditor boxed';
        if ($fixedWidth) {
            $str .= ' fixedWidthFont';
        }
        $str .= '" data-track-changed="1" data-enter-mode="br" data-no-strike="1" ' .
            'data-original-html="' . Html::encode($originalHtml) . '" ' .
            'id="' . $htmlId . '_wysiwyg" title="' . Html::encode($type->title) . '">';
        $str .= HTMLTools::prepareHTMLForCkeditor($data);
        $str .= '</div>';

        if (HTMLTools::cleanSimpleHtml($originalHtml) != HTMLTools::cleanSimpleHtml($data)) {
            $str .= '<div class="modifiedActions"><button class="btn-link resetText" type="button">';
            $str .= \Yii::t('amend', 'revert_changes');
            $str .= '</button></div>';
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @return bool
     */
    abstract public function isEmpty();

    /**
     * @return string
     */
    abstract public function getMotionFormField();

    /**
     * @return string
     */
    abstract public function getAmendmentFormField();

    /**
     * @param $data
     * @throws FormError
     */
    abstract public function setMotionData($data);

    /**
     * @param $data
     * @throws FormError
     */
    abstract public function setAmendmentData($data);

    /**
     * @param bool $isRight
     * @return string
     */
    abstract public function getSimple($isRight);

    /**
     * @return string
     */
    public function getMotionPlainHtml()
    {
        return $this->getSimple(false);
    }

    /**
     * @return string
     */
    public function getAmendmentPlainHtml()
    {
        return $this->getSimple(false);
    }

    /**
     * @return string
     */
    abstract public function getAmendmentFormatted();

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    abstract public function printMotionToPDF(IPDFLayout $pdfLayout, \FPDI $pdf);

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    abstract public function printAmendmentToPDF(IPDFLayout $pdfLayout, \FPDI $pdf);

    /**
     * @param bool $isRight
     * @param Content $content
     */
    abstract public function printMotionTeX($isRight, Content $content);

    /**
     * @param bool $isRight
     * @param Content $content
     */
    abstract public function printAmendmentTeX($isRight, Content $content);

    /**
     */
    public function cleanupAmendmentText()
    {
        return;
    }

    /**
     * @return string
     */
    abstract public function getMotionODS();

    /**
     * @return string
     */
    abstract public function getAmendmentODS();

    /**
     * @param Text $odt
     * @return mixed
     */
    abstract public function printMotionToODT(Text $odt);

    /**
     * @param Text $odt
     * @return mixed
     */
    abstract public function printAmendmentToODT(Text $odt);

    /**
     * @return string
     */
    abstract public function getMotionPlainText();

    /**
     * @return string
     */
    abstract public function getAmendmentPlainText();

    /**
     * @param Base $controller
     * @param CommentForm $commentForm
     * @param int[] $openedComments
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function showMotionView(Base $controller, $commentForm, $openedComments)
    {
        return $this->getSimple(false);
    }

    /**
     * @param $text
     * @return bool
     */
    abstract public function matchesFulltextSearch($text);
}
