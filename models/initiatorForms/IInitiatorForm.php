<?php

namespace app\models\initiatorForms;

use app\controllers\Base;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\forms\AmendmentEditForm;
use app\models\forms\MotionEditForm;

abstract class IInitiatorForm
{
    const ONLY_INITIATOR = 0;
    const WITH_SUPPORTER = 1;

    /**
     * @return IInitiatorForm[]
     */
    public static function getImplementations()
    {
        return [
            static::ONLY_INITIATOR => OnlyInitiator::class,
            static::WITH_SUPPORTER => WithSupporters::class,
        ];
    }

    /**
     * @param int $formId
     * @param ConsultationMotionType $motionType
     * @param string $settings
     * @return IInitiatorForm
     * @throws Internal
     */
    public static function getImplementation($formId, ConsultationMotionType $motionType, $settings)
    {
        switch ($formId) {
            case 0:
                return new OnlyInitiator($motionType);
            case 1:
                return new WithSupporters($motionType, $settings);
            default:
                throw new Internal('Supporter form type not found');
        }
    }

    /**
     * @return string
     */
    public static function getTitle()
    {
        return '';
    }

    /**
     * @return string|null
     */
    abstract public function getSettings();

    /**
     * @param array $settings
     */
    abstract public function setSettings($settings);

    /**
     * @return bool
     */
    public static function hasSupporters()
    {
        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    abstract public function isValidName($name);

    /**
     * @throws FormError
     */
    abstract public function validateMotion();

    /**
     * @param Motion $motion
     * @throws FormError
     */
    abstract public function submitMotion(Motion $motion);

    /**
     * @throws FormError
     */
    abstract public function validateAmendment();

    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    abstract public function submitAmendment(Amendment $amendment);

    /**
     * @param ConsultationMotionType $motionType
     * @param MotionEditForm $editForm
     * @param Base $controller
     * @return string
     */
    abstract public function getMotionForm(
        ConsultationMotionType $motionType,
        MotionEditForm $editForm,
        Base $controller
    );

    /**
     * @param ConsultationMotionType $motionType
     * @param AmendmentEditForm $editForm
     * @param Base $controller
     * @return string
     */
    abstract public function getAmendmentForm(
        ConsultationMotionType $motionType,
        AmendmentEditForm $editForm,
        Base $controller
    );

    /**
     * @param Motion $motion
     * @return MotionSupporter[]
     */
    abstract public function getMotionSupporters(Motion $motion);

    /**
     * @param Amendment $amendment
     * @return AmendmentSupporter[]
     */
    abstract public function getAmendmentSupporters(Amendment $amendment);
}
