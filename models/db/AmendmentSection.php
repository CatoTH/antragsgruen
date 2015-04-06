<?php

namespace app\models\db;

use app\components\diff\Diff;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\components\diff\Engine;
/**
 * @package app\models\db
 *
 * @property int $amendmentId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $metadata
 *
 * @property Amendment $amendment
 * @property ConsultationSettingsMotionSection $consultationSetting
 * @property AmendmentSection
 */
class AmendmentSection extends IMotionSection
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentSection';
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
    public function getAmendment()
    {
        return $this->hasOne(Amendment::className(), ['id' => 'amendmentId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'sectionId'], 'required'],
            [['amendmentId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    /**
     * @return string
     * @throws Internal
     */
    public function getInlineDiffHtml()
    {
        if ($this->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Only supported for simple HTML');
        }
        $strPre = null;
        foreach ($this->amendment->motion->sections as $section) {
            if ($section->sectionId == $this->sectionId) {
                $strPre = $section->data;
            }
        }
        if ($strPre === null) {
            throw new Internal('Original version not found');
        }
        //$debug = ($this->sectionId == 4);
        $debug = false;
        return Diff::computeDiff($strPre, $this->data, $debug);
    }
}
