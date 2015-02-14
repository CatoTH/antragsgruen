<?php

namespace app\models\initiatorViews;

use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\User;
use app\models\exceptions\FormError;

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
     * @param User $initiator
     * @return string
     */
    public function getInitiatorForm(Consultation $consultation, User $initiator);
}
