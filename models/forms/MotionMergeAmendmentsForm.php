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
    public $origMotion;
    public $newMotion;

    /** @var array */
    public $sections;
    public $amendStatus;

    /** @var MotionSection[] */
    public $motionSections;

    /**
     * @param Motion $origMotion
     * @param Motion $newMotion
     */
    public function __construct(Motion $origMotion, Motion $newMotion)
    {
        parent::__construct();
        $this->origMotion = $origMotion;
        $this->newMotion  = $newMotion;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['origMotion', 'newMotion'], 'required'],
            [['sections', 'amendStatus'], 'safe']
        ];
    }

    /**
     * @return Motion
     * @throws Internal
     */
    public function createNewMotion()
    {
        $this->newMotion->titlePrefix    = $this->origMotion->getNewTitlePrefix();
        $this->newMotion->motionTypeId   = $this->origMotion->motionTypeId;
        $this->newMotion->agendaItemId   = $this->origMotion->agendaItemId;
        $this->newMotion->consultationId = $this->origMotion->consultationId;
        $this->newMotion->parentMotionId = $this->origMotion->id;
        $this->newMotion->cache          = '';
        $this->newMotion->title          = '';
        $this->newMotion->dateCreation   = date('Y-m-d H:i:s');
        $this->newMotion->status         = Motion::STATUS_DRAFT;
        if (!$this->newMotion->save()) {
            var_dump($this->newMotion->getErrors());
            throw new Internal();
        }

        foreach ($this->origMotion->motionType->motionSections as $sectionType) {
            $section            = new MotionSection();
            $section->sectionId = $sectionType->id;
            $section->motionId  = $this->newMotion->id;
            $section->cache     = '';
            $section->data      = '';
            $section->dataRaw   = '';
            $section->refresh();

            if ($section->getSettings()->type == ISectionType::TYPE_TEXT_SIMPLE) {
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

        $this->newMotion->refreshTitle();
        $this->newMotion->save();

        return $this->newMotion;
    }
}
