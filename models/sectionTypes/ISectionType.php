<?php
namespace app\models\sectionTypes;

use app\controllers\Base;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use yii\helpers\Html;

abstract class ISectionType
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;

    /** @var  MotionSection */
    protected $section;

    /**
     * @param MotionSection $section
     */
    public function __construct(MotionSection $section)
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
     * @return string
     */
    protected function getTextFormField()
    {
        $type = $this->section->consultationSetting;

        $str = '<fieldset class="form-group wysiwyg-textarea"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="0"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen > 0) {
            $str .= '<div class="max_len_hint">';
            $str .= '<div class="calm">Maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '<div class="alert">Text zu lang - maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '</div>';
        }

        $str .= '<div class="textFullWidth">';
        $str .= '<div><textarea id="sections_' . $type->id . '" name="sections[' . $type->id . ']" rows="5" cols="80">';
        $str .= Html::encode($this->section->data);
        $str .= '</textarea></div></div>';
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
    abstract public function getFormField();

    /**
     * @param $data
     * @throws FormError
     */
    abstract public function setData($data);

    /**
     * @return string
     */
    abstract public function showSimple();


    /**
     * @param Base $controller
     * @param int[] $openedComments
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function showMotionView(Base $controller, $openedComments)
    {
        return $this->showSimple();
    }
}
