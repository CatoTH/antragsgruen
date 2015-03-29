<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\exceptions\FormError;
use yii\helpers\Html;

class TextSimple extends ISectionType
{

    /**
     * @return string
     */
    public function getFormField()
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
     * @param $data
     * @throws FormError
     */
    public function setData($data)
    {
        $this->section->data = HTMLTools::cleanSimpleHtml($data);
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        return $this->section->data;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
