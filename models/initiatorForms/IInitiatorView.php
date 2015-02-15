<?php

namespace app\models\initiatorForms;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\forms\MotionEditForm;

interface IInitiatorView
{
    /**
     * @param string $name
     * @return bool
     */
    public function isValidName($name);


    /**
     * @param bool $allowOtherInitiators
     * @return User|null
     */
    public function getSubmitPerson($allowOtherInitiators);


    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function submitInitiatorViewMotion(Motion $motion);


    /**
     * @param Amendment $amendment
     * @throws FormError
     */
    public function submitInitiatorViewAmendment(Amendment $amendment);


    /**
     * @param Consultation $consultation
     * @param MotionEditForm $editForm
     * @return string
     */
    public function getMotionInitiatorForm(Consultation $consultation, MotionEditForm $editForm);
}
