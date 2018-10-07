<?php

namespace app\async\models;

use app\models\db\ISupporter;

class Person extends TransferrableObject
{
    public $type;
    public $name;
    public $organization;
    public $resolutionDate;

    /**
     * @param ISupporter $supporter
     * @return Person
     * @throws \Exception
     */
    public static function createFromDbIMotionObject(ISupporter $supporter)
    {
        $person                 = new Person('');
        $person->type           = IntVal($supporter->personType);
        $person->name           = $supporter->name;
        $person->organization   = $supporter->organization;
        $person->resolutionDate = $supporter->resolutionDate;
        return $person;
    }
}
