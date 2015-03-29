<?php

namespace app\models\db;

use yii\db\ActiveRecord;
use app\models\exceptions\Internal;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $motionId
 * @property int $sectionId
 * @property string $data
 *
 * @property Motion $motion
 * @property ConsultationSettingsMotionSection $consultationSetting
 */
class MotionSection extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motionSection';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationSetting()
    {
        return $this->hasOne(ConsultationSettingsMotionSection::className(), ['id' => 'sectionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId', 'sectionId'], 'required'],
            [['motionId', 'sectionId'], 'number'],
        ];
    }

    /**
     * @return bool
     */
    public function checkLength()
    {
        // @TODO
        return true;
    }


    /**
     * @return string
     */
    protected function getFormFieldTitle()
    {
        // @TODO Max Length
        $type = $this->consultationSetting;
        return '<fieldset class="form-group">
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="text" class="form-control" id="sections_' . $type->id . '"' .
        ' name="sections[' . $type->id . ']" value="' . Html::encode($this->data) . '">
        </fieldset>';
    }

    /**
     * @return string
     */
    protected function getFormFieldImage()
    {
        return '@TODO'; // @TODO
    }

    /**
     * @param bool $fullHtml
     * @return string
     */
    protected function getFormFieldText($fullHtml)
    {
        $type = $this->consultationSetting;

        $str = '<fieldset class="form-group wysiwyg-textarea"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="' . ($fullHtml ? 1 : 0) . '"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen > 0) {
            $str .= '<div class="max_len_hint">';
            $str .= '<div class="calm">Maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '<div class="alert">Text zu lang - maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '</div>';
        }

        $str .= '<div class="textFullWidth">';
        $str .= '<div><textarea id="sections_' . $type->id . '" name="sections[' . $type->id . ']" rows="5" cols="80">';
        $str .= Html::encode($this->data);
        $str .= '</textarea></div></div>';
        $str .= '</fieldset>';

        return $str;
    }

    /**
     * @return string
     * @throws Internal
     */
    public function getFormField()
    {
        switch ($this->consultationSetting->type) {
            case ConsultationSettingsMotionSection::TYPE_TITLE:
                return $this->getFormFieldTitle();
            case ConsultationSettingsMotionSection::TYPE_TEXT_HTML:
                return $this->getFormFieldText(true);
            case ConsultationSettingsMotionSection::TYPE_TEXT_SIMPLE:
                return $this->getFormFieldText(false);
            case ConsultationSettingsMotionSection::TYPE_IMAGE:
                return $this->getFormFieldImage();
        }
        throw new Internal('Unknown Field Type: ' . $this->consultationSetting->type);
    }


    /**
     * @param string $data
     */
    public function setData($data)
    {
        // @TODO Filtering
        $this->data = $data;
    }
}
