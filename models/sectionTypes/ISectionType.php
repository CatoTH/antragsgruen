<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\models\settings\MotionSection;
use app\models\db\{Consultation, IMotionSection, Motion};
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use CatoTH\HTML2OpenDocument\Text;
use yii\helpers\Html;

abstract class ISectionType
{
    // Synchronize with MotionTypeEdit.ts
    public const TYPE_TITLE           = 0;
    public const TYPE_TEXT_SIMPLE     = 1;
    public const TYPE_TEXT_HTML       = 2;
    public const TYPE_IMAGE           = 3;
    public const TYPE_TABULAR         = 4;
    public const TYPE_PDF_ATTACHMENT  = 5;
    public const TYPE_PDF_ALTERNATIVE = 6;
    public const TYPE_VIDEO_EMBED     = 7;

    protected const TYPE_API_TITLE = 'Title';
    protected const TYPE_API_TEXT_SIMPLE = 'TextSimple';
    protected const TYPE_API_TEXT_HTML = 'TextHTML';
    protected const TYPE_API_IMAGE = 'Image';
    protected const TYPE_API_TABULAR = 'TabularData';
    protected const TYPE_API_PDF_ATTACHMENT = 'PDFAttachment';
    protected const TYPE_API_PDF_ALTERNATIVE = 'PDFAlternative';
    protected const TYPE_API_VIDEO_EMBED = 'VideoEmbed';

    protected IMotionSection $section;
    protected bool $absolutizeLinks = false;
    protected ?string $titlePrefix = null;
    protected bool $defaultOnlyDiff = true;
    protected ?Motion $motionContext = null;

    public function __construct(IMotionSection $section)
    {
        $this->section = $section;
    }

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            static::TYPE_TITLE           => \Yii::t('structure', 'section_title'),
            static::TYPE_TEXT_SIMPLE     => \Yii::t('structure', 'section_text'),
            static::TYPE_TEXT_HTML       => \Yii::t('structure', 'section_html'),
            static::TYPE_IMAGE           => \Yii::t('structure', 'section_image'),
            static::TYPE_TABULAR         => \Yii::t('structure', 'section_tabular'),
            static::TYPE_PDF_ATTACHMENT  => \Yii::t('structure', 'section_pdf_attachment'),
            static::TYPE_PDF_ALTERNATIVE => \Yii::t('structure', 'section_pdf_alternative'),
            static::TYPE_VIDEO_EMBED     => \Yii::t('structure', 'section_video_embed'),
        ];
    }

    public static function typeIdToApi(int $type): string
    {
        return match ($type) {
            static::TYPE_TITLE => static::TYPE_API_TITLE,
            static::TYPE_TEXT_SIMPLE => static::TYPE_API_TEXT_SIMPLE,
            static::TYPE_TEXT_HTML => static::TYPE_API_TEXT_HTML,
            static::TYPE_IMAGE => static::TYPE_API_IMAGE,
            static::TYPE_TABULAR => static::TYPE_API_TABULAR,
            static::TYPE_PDF_ALTERNATIVE => static::TYPE_API_PDF_ALTERNATIVE,
            static::TYPE_PDF_ATTACHMENT => static::TYPE_API_PDF_ATTACHMENT,
            static::TYPE_VIDEO_EMBED => static::TYPE_API_VIDEO_EMBED,
            default => 'Unknown',
        };
    }

    public function setAbsolutizeLinks(bool $absolutize): void
    {
        $this->absolutizeLinks = $absolutize;
    }

    public function setTitlePrefix(?string $titlePrefix): void
    {
        $this->titlePrefix = $titlePrefix;
    }

    public function setDefaultToOnlyDiff(bool $onlyDiff): void
    {
        $this->defaultOnlyDiff = $onlyDiff;
    }

    public function getSectionId(): int
    {
        return $this->section->sectionId;
    }

    public function getTitle(): string
    {
        $title = $this->section->getSettings()->title;
        if ($this->titlePrefix !== null) {
            $title = $this->titlePrefix . ': ' . $title;
        }
        return $title;
    }

    // This sets the motion in whose Context an amendment will be shown. This is relevant if the proposed procedure of an amendment
    // suggests replacing this amendment by one to another motion.
    public function setMotionContext(?Motion $motion): void
    {
        $this->motionContext = $motion;
    }


    protected function getFormLabel(): string
    {
        $type = $this->section->getSettings();
        $str  = '<label for="sections_' . $type->id . '"';
        if ($type->required) {
            $str .= ' class="required" data-required-str="' . Html::encode(\Yii::t('motion', 'field_required')) . '"';
        } else {
            $str .= ' class="optional" data-optional-str="' . Html::encode(\Yii::t('motion', 'field_optional')) . '"';
        }
        $str .= '>' . Html::encode($type->title) . '</label>';

        if ($type->getSettingsObj()->public === MotionSection::PUBLIC_NO) {
            $str .= '<div class="alert alert-info"><p>' . \Yii::t('motion', 'field_unpublic') . '</p></div>';
        }

        return $str;
    }

    abstract public function isEmpty(): bool;

    abstract public function isFileUploadType(): bool;

    abstract public function getMotionFormField(): string;

    abstract public function getAmendmentFormField(): string;

    /**
     * @param string $data
     * @throws FormError
     */
    abstract public function setMotionData($data): void;

    abstract public function deleteMotionData(): void;

    /**
     * @param array $data
     * @throws FormError
     */
    abstract public function setAmendmentData($data): void;

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

    abstract public function getAmendmentFormatted(string $htmlIdPrefix = ''): string;

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
     * @param int[] $openedComments
     */
    public function showMotionView(?CommentForm $commentForm, array $openedComments): string
    {
        return $this->getSimple(false);
    }

    abstract public function matchesFulltextSearch(string $text): bool;
}
