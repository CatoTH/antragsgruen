<?php

namespace app\models\forms;

use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\base\Model;

class MotionMergeAmendmentsForm extends Model
{
    /** @var Motion */
    public $motion;

    /** @var array */
    public $sections;
    public $amendStatus;

    /** @var MotionSection[] */
    public $motionSections;

    /**
     * @param Motion $motion
     */
    public function __construct(Motion $motion)
    {
        parent::__construct();
        $this->motion = $motion;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['sections', 'amendStatus'], 'safe']
        ];
    }

    /**
     * @return Motion
     * @throws Internal
     */
    public function saveMotion()
    {
        $newMotion                 = new Motion();
        $newMotion->titlePrefix    = $this->motion->getNewTitlePrefix();
        $newMotion->motionTypeId   = $this->motion->motionTypeId;
        $newMotion->agendaItemId   = $this->motion->agendaItemId;
        $newMotion->consultationId = $this->motion->consultationId;
        $newMotion->parentMotionId = $this->motion->id;
        $newMotion->title          = '';
        $newMotion->dateCreation   = date('Y-m-d H:i:s');
        $newMotion->status         = Motion::STATUS_DRAFT;
        if (!$newMotion->save()) {
            var_dump($newMotion->getErrors());
            throw new Internal();
        }

        foreach ($this->motion->motionType->motionSections as $sectionType) {
            $section            = new MotionSection();
            $section->sectionId = $sectionType->id;
            $section->motionId  = $newMotion->id;
            $section->cache     = '';
            $section->refresh();

            if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
                $consolidated = $this->sections[$section->sectionId]['consolidated'];
                $consolidated = str_replace('<li>&nbsp;</li>', '', $consolidated);
                /** @var TextSimple data */
                $section->getSectionType()->setMotionData($consolidated);
                $section->dataRaw = $this->sections[$section->sectionId]['raw'];
            } elseif (isset($this->sections[$section->sectionId])) {
                $section->getSectionType()->setMotionData($this->sections[$section->sectionId]);
            } else {
                // @TODO Images etc.
            }

            if (!$section->save()) {
                var_dump($section->getErrors());
                throw new Internal();
            }
            $this->motionSections[] = $section;
        }

        $newMotion->refreshTitle();
        $newMotion->save();

        return $newMotion;
    }
}
