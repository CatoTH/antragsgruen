<?php

namespace app\plugins\memberPetitions\commands;

use yii\console\Controller;

class CommandsController extends Controller
{
    public $defaultAction = 'hello';

    /**
     * Hello world
     */
    public function actionHello()
    {
        echo "Hello 🌏";
    }
}
