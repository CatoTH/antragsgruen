<?php

namespace app\async\models;

use app\models\db\MotionSupporter;

class Person extends TransferrableObject
{
    public $type;
    public $name;
    public $organization;
    public $resolutionDate;

    /**
     * @param MotionSupporter $supporter
     * @return Person
     * @throws \Exception
     */
    public static function createFromDbMotionObject(MotionSupporter $supporter)
    {
        $person                 = new Person('');
        $person->type           = $supporter->personType;
        $person->name           = $supporter->name;
        $person->organization   = $supporter->organization;
        $person->resolutionDate = $supporter->resolutionDate;
        return $person;
    }
}
