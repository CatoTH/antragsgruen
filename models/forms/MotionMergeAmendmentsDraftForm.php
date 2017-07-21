<?php

namespace app\models\forms;

use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\Internal;

class MotionMergeAmendmentsDraftForm
{
    /** @var Motion */
    public $origMotion;
    public $draft;

    /**
     * MotionMergeAmendmentsDraftForm constructor.
     * @param Motion $origMotion
     */
    public function __construct($origMotion)
    {
        $this->origMotion = $origMotion;
        $draftStati       = [Motion::STATUS_MERGING_DRAFT_PUBLIC, Motion::STATUS_MERGING_DRAFT_PRIVATE];
        $this->draft      = Motion::find()
            ->where(['parentMotionId' => $origMotion->id])
            ->andWhere(['status' => $draftStati])->one();
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
     * @param int $sectionId
     * @return MotionSection
     * @throws Internal
     */
    private function getSection($sectionId)
    {
        foreach ($this->draft->sections as $section) {
            if ($section->sectionId == $sectionId) {
                return $section;
            }
        }
        foreach ($this->origMotion->sections as $section) {
            if ($section->sectionId == $sectionId) {
                $newSection = new MotionSection();
                $newSection->setAttributes($section->getAttributes(), false);
                $newSection->motionId = $this->draft->id;
                return $newSection;
            }
        }
        throw new Internal('Invalid section');
    }

    /**
     * @param int $public
     * @param string[] $sections
     * @return Motion
     */
    public function save($public, $sections)
    {
        if ($public) {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PUBLIC;
        } else {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
        }
        $this->draft->save();

        foreach ($this->origMotion->sections as $origSection) {
            $section = $this->getSection($origSection->sectionId);
            if (isset($sections[$section->sectionId])) {
                $section->dataRaw = $sections[$section->sectionId];
                $section->data    = '';
            }
            $section->save();
        }

        return $this->draft;
    }
}
