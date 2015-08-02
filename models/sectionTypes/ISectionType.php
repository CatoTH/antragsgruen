<?php
namespace app\models\sectionTypes;

use app\components\opendocument\Text;
use app\controllers\Base;
use app\models\db\IMotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use yii\helpers\Html;

abstract class ISectionType
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;
    const TYPE_TABULAR     = 4;

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
        ];
    }

    /**
     * @param bool $fullHtml
     * @return string
     */
    protected function getTextMotionFormField($fullHtml)
    {
        $type   = $this->section->consultationSetting;
        $htmlId = 'sections_' . $type->id;

        $str = '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen != 0) {
            $len = abs($type->maxLen);
            $str .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign"></span> ';
            $str .= str_replace('%LEN%', $len, 'Max. %LEN% Zeichen (Aktuell: <span class="counter"></span>)');
            $str .= '</div>';
        }

        $str .= '<textarea name="sections[' . $type->id . ']"  id="sections_' . $type->id . '" ' .
            'title="' . Html::encode($type->title) . '">';
        $str .= Html::encode($this->section->data) . '</textarea>';
        $str .= '<div class="texteditor" id="' . $htmlId . '_wysiwyg" ' . 'title="' . Html::encode($type->title) . '">';
        $str .= $this->section->data;
        $str .= '</div>';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert"></span> ' . 'Der Text ist zu lang!';
            $str .= '</div>';
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @param bool $fullHtml
     * @param string $data
     * @return string
     */
    protected function getTextAmendmentFormField($fullHtml, $data)
    {
        $type     = $this->section->consultationSetting;
        $nameBase = 'sections[' . $type->id . ']';
        $htmlId   = 'sections_' . $type->id;

        $str = '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        $str .= '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        $str .= '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        $str .= '<div class="texteditor" data-track-changed="1" id="' . $htmlId . '_wysiwyg" ' .
            'title="' . Html::encode($type->title) . '">';
        $str .= $this->section->dataRaw;
        $str .= '</div>';

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
     * @return string
     */
    abstract public function getSimple();

    /**
     * @param \TCPDF $pdf
     */
    abstract public function printMotionToPDF(\TCPDF $pdf);

    /**
     * @param \TCPDF $pdf
     */
    abstract public function printAmendmentToPDF(\TCPDF $pdf);

    /**
     * @return string
     */
    abstract public function getMotionTeX();

    /**
     * @return string
     */
    abstract public function getAmendmentTeX();

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
        return $this->getSimple();
    }

    /**
     * @param $text
     * @return bool
     */
    abstract public function matchesFulltextSearch($text);
}
