<?php

namespace app\plugins\memberPetitions;

use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     */
    public static function getMotionUrlRoutes()
    {
        return [
            'write-petition-response' => 'memberPetitions/frontend/write-response',
        ];
    }
}
