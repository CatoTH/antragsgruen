<?php

namespace app\models\async;

use app\models\settings\JsonConfigTrait;

class Userdata
{
    use JsonConfigTrait;

    public $userId;
    public $username;
}
