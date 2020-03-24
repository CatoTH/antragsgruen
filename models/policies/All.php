<?php

namespace app\models\policies;

use app\components\DateTools;
use app\models\db\ConsultationMotionType;

class All extends IPolicy
{
    public static function getPolicyID(): int
    {
        return 1;
    }

    public static function getPolicyName(): string
    {
        return \Yii::t('structure', 'policy_all_title');
    }

    public function getOnCreateDescription(): string
    {
        return \Yii::t('structure', 'policy_all_desc');
    }

    public function getPermissionDeniedMotionMsg(): string
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_MOTIONS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return '';
    }

    public function getPermissionDeniedAmendmentMsg(): string
    {
        if (!$this->motionType->isInDeadline(ConsultationMotionType::DEADLINE_AMENDMENTS)) {
            return \Yii::t('structure', 'policy_deadline_over');
        }
        return '';
    }

    public function getPermissionDeniedSupportMsg(): string
    {
        return '';
    }

    public function getPermissionDeniedCommentMsg(): string
    {
        $deadlineType = ConsultationMotionType::DEADLINE_COMMENTS;
        if (!$this->motionType->isInDeadline($deadlineType)) {
            $deadlines = DateTools::formatDeadlineRanges($this->motionType->getDeadlinesByType($deadlineType));
            return \Yii::t('structure', 'policy_deadline_over_comm') . ' ' . $deadlines;
        }
        return '';
    }

    public function checkCurrUser(bool $allowAdmins = true, bool $assumeLoggedIn = false): bool
    {
        return true;
    }
}
