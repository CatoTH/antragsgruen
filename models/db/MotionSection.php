<?php

namespace app\models\db;

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\exceptions\FormError;
use app\models\sectionTypes\Image;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextHTML;
use app\models\sectionTypes\TextSimple;
use app\models\sectionTypes\Title;
use yii\db\ActiveRecord;
use app\models\exceptions\Internal;
use yii\helpers\Html;

/**
 * @package app\models\db
 *
 * @property int $motionId
 * @property int $sectionId
 * @property string $data
 * @property string $metadata
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
     * @return ISectionType
     * @throws Internal
     */
    public function getSectionType()
    {
        switch ($this->consultationSetting->type) {
            case ISectionType::TYPE_TITLE:
                return new Title($this);
            case ISectionType::TYPE_TEXT_HTML:
                return new TextHTML($this);
            case ISectionType::TYPE_TEXT_SIMPLE:
                return new TextSimple($this);
            case ISectionType::TYPE_IMAGE:
                return new Image($this);
        }
        throw new Internal('Unknown Field Type: ' . $this->consultationSetting->type);
    }
}
