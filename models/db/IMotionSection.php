<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\sectionTypes\Image;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextHTML;
use app\models\sectionTypes\TextSimple;
use app\models\sectionTypes\Title;
use yii\db\ActiveRecord;

/**
 * Class IMotionSection
 * @package app\models\db
 * @property string $data
 * @property int $sectionId
 * @property string $metadata
 * @property ConsultationSettingsMotionSection $consultationSetting
 */
class IMotionSection extends ActiveRecord
{
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


    /**
     * @return bool
     */
    public function checkLength()
    {
        // @TODO
        return true;
    }
}
