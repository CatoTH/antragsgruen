<?php

namespace app\async\models;

use app\models\db\User;

class Userdata extends TransferrableObject
{
    public $userId;
    public $username;

    /**
     * @param User $user
     * @return Userdata
     * @throws \Exception
     */
    public static function createFromDbObject(User $user)
    {
        $object           = new Userdata('');
        $object->userId   = $user->id;
        $object->username = $user->auth;
        return $object;
    }
}
