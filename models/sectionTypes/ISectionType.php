<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\models\db\AmendmentSection;
use app\models\db\Consultation;
use app\models\db\IMotionSection;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use app\views\pdfLayouts\IPDFLayout;
use CatoTH\HTML2OpenDocument\Text;
use setasign\Fpdi\TcpdfFpdi;
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

    protected $absolutizeLinks = false;

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
     * @param boolean $absolutize
     */
    public function setAbsolutizeLinks($absolutize)
    {
        $this->absolutizeLinks = $absolutize;
    }


    /**
     * @return string
     */
    protected function getFormLabel()
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
     * @param array $data
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
     * @param string $sectionTitlePrefix
     * @return string
     */
    abstract public function getAmendmentFormatted($sectionTitlePrefix = '');

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    abstract public function printMotionToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf);

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    abstract public function printAmendmentToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf);

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
