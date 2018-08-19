<?php

namespace app\async\models;

use app\models\db\Consultation;
use app\models\db\User;

class Userdata extends TransferrableObject
{
    public $userId;
    public $username;
    /** @var UserPrivilege|null */
    public $privileges;

    /**
     * @param User $user
     * @param Consultation $consultation
     * @return Userdata
     * @throws \Exception
     */
    public static function createFromDbObject(User $user, Consultation $consultation)
    {
        $object           = new Userdata('');
        $object->userId   = IntVal($user->id);
        $object->username = $user->auth;
        $object->privileges = UserPrivilege::createFromDbObject($user->getConsultationPrivilege($consultation));
        return $object;
    }
}
