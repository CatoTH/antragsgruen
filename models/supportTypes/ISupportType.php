<?php

namespace app\models\supportTypes;

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

abstract class ISupportType
{
    const ONLY_INITIATOR        = 0;
    const GIVEN_BY_INITIATOR    = 1;
    const COLLECTING_SUPPORTERS = 2;

    const LIKEDISLIKE_LIKE    = 1;
    const LIKEDISLIKE_DISLIKE = 2;
    const LIKEDISLIKE_SUPPORT = 4;

    /** @var bool */
    protected $adminMode = false;

    /** @var bool */
    protected $hasOrganizations = false;

    /**
     * @return ISupportType[]
     */
    public static function getImplementations()
    {
        return [
            static::ONLY_INITIATOR        => OnlyInitiator::class,
            static::GIVEN_BY_INITIATOR    => GivenByInitiator::class,
            static::COLLECTING_SUPPORTERS => CollectBeforePublish::class,
        ];
    }

    /**
     * @param int $formId
     * @param ConsultationMotionType $motionType
     * @param string $settings
     * @return ISupportType
     * @throws Internal
     */
    public static function getImplementation($formId, ConsultationMotionType $motionType, $settings)
    {
        switch ($formId) {
            case static::ONLY_INITIATOR:
                return new OnlyInitiator($motionType, $settings);
            case static::GIVEN_BY_INITIATOR:
                return new GivenByInitiator($motionType, $settings);
            case static::COLLECTING_SUPPORTERS:
                return new CollectBeforePublish($motionType, $settings);
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
     * @param bool $set
     */
    public function setAdminMode($set)
    {
        $this->adminMode = $set;
    }


    /**
     * @return bool
     */
    public function hasOrganizations()
    {
        return $this->hasOrganizations;
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
    public static function hasInitiatorGivenSupporters()
    {
        return false;
    }

    /**
     * @return bool
     */
    public static function collectSupportersBeforePublication()
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

    /**
     * @return int
     */
    abstract public function getMinNumberOfSupporters();
}
