<?php

namespace app\models\supportTypes;

use app\controllers\Base;
use app\models\db\ConsultationMotionType;
use app\models\forms\{AmendmentEditForm, MotionEditForm};

class NoInitiator extends SupportBase
{
    public static function getTitle(): string
    {
        return \Yii::t('structure', 'supp_no_initiator');
    }

    public static function hasInitiatorGivenSupporters(): bool
    {
        return false;
    }

    protected function fixSettings(): void
    {
        $this->settingsObject->minSupporters       = 0;
        $this->settingsObject->allowMoreSupporters = false;
    }

    public function getMotionForm(ConsultationMotionType $motionType, MotionEditForm $editForm, Base $controller): string
    {
        return '';
    }

    public function getAmendmentForm(ConsultationMotionType $motionType, AmendmentEditForm $editForm, Base $controller): string
    {
        return '';
    }
}
