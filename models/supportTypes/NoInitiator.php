<?php

declare(strict_types=1);

namespace app\models\supportTypes;

use app\controllers\Base;
use app\models\db\{Amendment, ConsultationMotionType, ISupporter, Motion};
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

    /**
     * @param ISupporter[] $supporters
     */
    public function validateMotion(ISupporter $initiator, array $supporters): void
    {
    }

    /**
     * @param ISupporter[] $supporters
     */
    public function validateAmendment(ISupporter $initiator, array $supporters): void
    {
    }

    public function submitMotion(Motion $motion, array $supporters): void
    {
    }

    public function submitAmendment(Amendment $amendment, array $supporters): void
    {
    }

    public function getMotionSupporters(Motion $motion): array
    {
        return [];
    }

    public function getAmendmentSupporters(Amendment $amendment): array
    {
        return [];
    }
}
