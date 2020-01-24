<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\models\db\{Consultation, IMotionSection, MotionSection};
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use CatoTH\HTML2OpenDocument\Text;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;

abstract class ISectionType
{
    const TYPE_TITLE           = 0;
    const TYPE_TEXT_SIMPLE     = 1;
    const TYPE_TEXT_HTML       = 2;
    const TYPE_IMAGE           = 3;
    const TYPE_TABULAR         = 4;
    const TYPE_PDF_ATTACHMENT  = 5;
    const TYPE_PDF_ALTERNATIVE = 6;

    /** @var IMotionSection */
    protected $section;

    protected $absolutizeLinks = false;

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
            static::TYPE_TITLE           => \yii::t('structure', 'section_title'),
            static::TYPE_TEXT_SIMPLE     => \yii::t('structure', 'section_text'),
            static::TYPE_TEXT_HTML       => \yii::t('structure', 'section_html'),
            static::TYPE_IMAGE           => \yii::t('structure', 'section_image'),
            static::TYPE_TABULAR         => \yii::t('structure', 'section_tabular'),
            static::TYPE_PDF_ATTACHMENT  => \yii::t('structure', 'section_pdf_attachment'),
            static::TYPE_PDF_ALTERNATIVE => \yii::t('structure', 'section_pdf_alternative'),
        ];
    }

    public function setAbsolutizeLinks(bool $absolutize)
    {
        $this->absolutizeLinks = $absolutize;
    }


    protected function getFormLabel(): string
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $str  = '<label for="sections_' . $type->id . '"';
        if ($type->required) {
            $str .= ' class="required" data-required-str="' . Html::encode(\Yii::t('motion', 'field_required')) . '"';
        } else {
            $str .= ' class="optional" data-optional-str="' . Html::encode(\Yii::t('motion', 'field_optional')) . '"';
        }
        $str .= '>' . Html::encode($type->title) . '</label>';
        return $str;
    }

    abstract public function isEmpty(): bool;

    abstract public function getMotionFormField(): string;

    abstract public function getAmendmentFormField(): string;

    /**
     * @param $data
     * @throws FormError
     */
    abstract public function setMotionData($data);

    abstract public function deleteMotionData();

    /**
     * @param array $data
     * @throws FormError
     */
    abstract public function setAmendmentData($data);

    /**
     * @param bool $isRight
     * @param bool $showAlways
     * @return string
     */
    abstract public function getSimple($isRight, $showAlways = false);

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
     * @param string $sectionTitlePrefix
     * @return string
     */
    abstract public function getAmendmentFormatted($sectionTitlePrefix = '');

    /**
     * @param IPDFLayout $pdfLayout
     * @param Fpdi $pdf
     */
    abstract public function printMotionToPDF(IPDFLayout $pdfLayout, Fpdi $pdf);

    /**
     * @param IPDFLayout $pdfLayout
     * @param Fpdi $pdf
     */
    abstract public function printAmendmentToPDF(IPDFLayout $pdfLayout, Fpdi $pdf);

    /**
     * @param bool $isRight
     * @param Content $content
     * @param Consultation $consultation
     */
    abstract public function printMotionTeX($isRight, Content $content, Consultation $consultation);

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
     */
    abstract public function printMotionToODT(Text $odt);

    /**
     * @param Text $odt
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
     * @param CommentForm|null $commentForm
     * @param int[] $openedComments
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function showMotionView($commentForm, $openedComments)
    {
        return $this->getSimple(false);
    }

    /**
     * @param $text
     * @return bool
     */
    abstract public function matchesFulltextSearch($text);
}
