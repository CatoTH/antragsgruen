<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\models\db\{Consultation, IMotionSection, MotionSection};
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use CatoTH\HTML2OpenDocument\Text;
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

    const TYPE_API_TITLE = 'Title';
    const TYPE_API_TEXT_SIMPLE = 'TextSimple';
    const TYPE_API_TEXT_HTML = 'TextHTML';
    const TYPE_API_IMAGE = 'Image';
    const TYPE_API_TABULAR = 'TabularData';
    const TYPE_API_PDF_ATTACHMENT = 'PDFAttachment';
    const TYPE_API_PDF_ALTERNATIVE = 'PDFAlternative';

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

    public static function typeIdToApi(int $type): string
    {
        switch ($type) {
            case static::TYPE_TITLE:
                return static::TYPE_API_TITLE;
            case static::TYPE_TEXT_SIMPLE:
                return static::TYPE_API_TEXT_SIMPLE;
            case static::TYPE_TEXT_HTML:
                return static::TYPE_API_TEXT_HTML;
            case static::TYPE_IMAGE:
                return static::TYPE_API_IMAGE;
            case static::TYPE_TABULAR:
                return static::TYPE_API_TABULAR;
            case static::TYPE_API_PDF_ALTERNATIVE:
                return static::TYPE_API_PDF_ALTERNATIVE;
            case static::TYPE_API_PDF_ATTACHMENT:
                return static::TYPE_API_PDF_ATTACHMENT;
            default:
                return 'Unknown';
        }
    }

    public function setAbsolutizeLinks(bool $absolutize): void
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

    abstract public function getSimple(bool $isRight, bool $showAlways = false): string;

    public function getMotionPlainHtml(): string
    {
        return $this->getSimple(false);
    }

    public function getMotionEmailHtml(): string
    {
        return $this->getSimple(false);
    }

    public function getAmendmentPlainHtml(): string
    {
        return $this->getSimple(false);
    }

    abstract public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string;

    abstract public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void;

    abstract public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void;

    abstract public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void;

    abstract public function printAmendmentTeX(bool $isRight, Content $content): void;

    abstract public function getMotionODS(): string;

    abstract public function getAmendmentODS(): string;

    abstract public function printMotionToODT(Text $odt): void;

    abstract public function printAmendmentToODT(Text $odt): void;

    abstract public function getMotionPlainText(): string;

    abstract public function getAmendmentPlainText(): string;

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
