<?php

namespace app\models\forms;

use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;

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
            $this->draft->dateCreation   = date('Y-m-d H:i:s');
        } else {
            $this->draft = new Motion();
            $this->draft->setAttributes($this->origMotion->getAttributes(), false);
            $this->draft->id             = null;
            $this->draft->dateCreation   = date('Y-m-d H:i:s');
            $this->draft->status         = Motion::STATUS_MERGING_DRAFT_PRIVATE;
            $this->draft->titlePrefix    = '';
            $this->draft->parentMotionId = $this->origMotion->id;
        }
    }

    /**
     * @param int $public
     * @param string[] $sections
     */
    public function save($public, $sections)
    {
        if ($public) {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PUBLIC;
        } else {
            $this->draft->status = Motion::STATUS_MERGING_DRAFT_PRIVATE;
        }
        $this->draft->save();

        foreach ($sections as $section) {

        }
    }
}
