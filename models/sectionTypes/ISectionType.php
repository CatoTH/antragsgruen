<?php
namespace app\models\sectionTypes;

use app\controllers\Base;
use app\models\db\IMotionSection;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\forms\CommentForm;
use yii\helpers\Html;

abstract class ISectionType
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;

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
            static::TYPE_TITLE       => 'Titel',
            static::TYPE_TEXT_SIMPLE => 'Text',
            static::TYPE_TEXT_HTML   => 'Text (erweitert)',
            static::TYPE_IMAGE       => 'Bild',
        ];
    }

    /**
     * @param bool $fullHtml
     * @return string
     */
    protected function getTextMotionFormField($fullHtml)
    {
        $type = $this->section->consultationSetting;
        $htmlId   = 'sections_' . $type->id;

        $str = '<fieldset class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen > 0) {
            $str .= '<div class="maxLenHint">';
            $str .= '<div class="calm">Maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '<div class="alert">Text zu lang - maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '</div>';
        }

        $str .= '<textarea name="sections[' . $type->id . ']"  id="sections_' . $type->id . '">';
        $str .= Html::encode($this->section->data) . '</textarea>';
        $str .= '<div class="texteditor" id="' . $htmlId . '_wysiwyg">';
        $str .= $this->section->data;
        $str .= '</div>';
        $str .= '</fieldset>';

        return $str;
    }

    /**
     * @param bool $fullHtml
     * @return string
     */
    protected function getTextAmendmentFormField($fullHtml)
    {
        $type     = $this->section->consultationSetting;
        $nameBase = 'sections[' . $type->id . ']';
        $htmlId   = 'sections_' . $type->id;

        $str = '<fieldset class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="' . ($fullHtml ? '1' : '0') . '"';
        $str .= '><label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen > 0) {
            $str .= '<div class="maxLenHint">';
            $str .= '<div class="calm">Maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '<div class="alert">Text zu lang - maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '</div>';
        }

        $str .= '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '"></textarea>';
        $str .= '<textarea name="' . $nameBase . '[consolidated]" class="consolidated"></textarea>';
        $str .= '<div class="texteditor" data-track-changed="1" id="' . $htmlId . '_wysiwyg">';
        $str .= $this->section->data;
        $str .= '</div>';

        $str .= '</fieldset>';

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
    abstract public function showSimple();


    /**
     * @param Base $controller
     * @param CommentForm $commentForm
     * @param int[] $openedComments
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function showMotionView(Base $controller, $commentForm, $openedComments)
    {
        return $this->showSimple();
    }
}
