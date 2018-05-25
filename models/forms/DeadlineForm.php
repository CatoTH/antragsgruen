<?php

namespace app\models\forms;

use app\models\db\ConsultationMotionType;

class DeadlineForm
{
    public $deadlinesMotions;
    public $deadlinesAmendments;
    public $deadlinesComments;
    public $deadlinesMerging;

    /**
     * @param ConsultationMotionType $motionType
     * @return DeadlineForm
     */
    public static function createFromMotionType(ConsultationMotionType $motionType)
    {
        $form                      = new DeadlineForm();
        $form->deadlinesMotions    = $motionType->getDeadlines(ConsultationMotionType::DEADLINE_MOTIONS);
        $form->deadlinesAmendments = $motionType->getDeadlines(ConsultationMotionType::DEADLINE_AMENDMENTS);
        $form->deadlinesComments   = $motionType->getDeadlines(ConsultationMotionType::DEADLINE_COMMENTS);
        $form->deadlinesMerging    = $motionType->getDeadlines(ConsultationMotionType::DEADLINE_MERGING);
        return $form;
    }

    /**
     * @param array $input
     * @return DeadlineForm
     */
    public static function createFromInput($input)
    {
        $form = new DeadlineForm();
        // @TODO
        return $form;
    }

    /**
     * @return boolean
     */
    public function isSimpleConfiguration()
    {
        if (count($this->deadlinesComments) > 0 || count($this->deadlinesMerging) > 0) {
            return false;
        }
        $simpleMotion    = (
            count($this->deadlinesMotions) === 0 ||
            (count($this->deadlinesMotions) === 1 && !$this->deadlinesMotions[0]['start'])
        );
        $simpleAmendment = (
            count($this->deadlinesAmendments) === 0 ||
            (count($this->deadlinesAmendments) === 1 && !$this->deadlinesAmendments[0]['start'])
        );
        return ($simpleMotion && $simpleAmendment);
    }

    /**
     * @return null|string
     */
    public function getSimpleMotionsDeadline()
    {
        if (count($this->deadlinesMotions) === 0) {
            return null;
        }
        return $this->deadlinesMotions[0]['end'];
    }

    /**
     * @return null|string
     */
    public function getSimpleAmendmentsDeadline()
    {
        if (count($this->deadlinesAmendments) === 0) {
            return null;
        }
        return $this->deadlinesAmendments[0]['end'];
    }

    /**
     * @return array
     */
    public function generateDeadlineArray()
    {
        return [
            ConsultationMotionType::DEADLINE_MOTIONS    => $this->deadlinesMotions,
            ConsultationMotionType::DEADLINE_AMENDMENTS => $this->deadlinesAmendments,
            ConsultationMotionType::DEADLINE_MERGING    => $this->deadlinesMerging,
            ConsultationMotionType::DEADLINE_COMMENTS   => $this->deadlinesComments,
        ];
    }
}
