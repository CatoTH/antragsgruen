<?php

namespace app\models\forms;

use app\models\db\Motion;
use app\models\db\MotionSection;

class MotionMergeAmendmentsDraftForm
{
    /** @var Motion */
    public $origMotion;
    public $draft;

    /**
     * MotionMergeAmendmentsDraftForm constructor.
     *
     * @param Motion $origMotion
     */
    public function __construct($origMotion)
    {
        $this->origMotion = $origMotion;
        $draftStatuses    = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];
        $this->draft      = Motion::find()
                                  ->where(['parentMotionId' => $origMotion->id])
                                  ->andWhere(['status' => $draftStatuses])->one();
        if ($this->draft) {
            $this->draft->dateCreation = date('Y-m-d H:i:s');
        } else {
            $this->draft = new Motion();
            $this->draft->setAttributes($this->origMotion->getAttributes(), false);
            $this->draft->id             = null;
            $this->draft->dateCreation   = date('Y-m-d H:i:s');
            $this->draft->status         = Motion::STATUS_MERGING_DRAFT_PRIVATE;
            $this->draft->titlePrefix    = '';
            $this->draft->parentMotionId = $this->origMotion->id;
            $this->draft->slug           = null;
        }
    }

    /**
     * @param int $public
     * @param string $data
     *
     * @return Motion
     */
    public function save($public, $data)
    {
        if ($public) {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PUBLIC;
        } else {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
        }
        $this->draft->save();

        $section = null;
        foreach ($this->draft->sections as $existingSection) {
            $section = $existingSection;
        }
        if (!$section) {
            $section = new MotionSection();
            $section->setAttributes($this->origMotion->sections[0]->getAttributes(), false);
            $section->motionId = $this->draft->id;
        }
        $section->dataRaw = $data;
        $section->data    = '';
        $section->save();

        foreach ($this->draft->sections as $oldSection) {
            if ($oldSection->sectionId !== $section->sectionId) {
                try {
                    $oldSection->delete();
                } catch (\Throwable $e) {
                    var_dump($e);
                    die();
                }
            }
        }

        return $this->draft;
    }
}
