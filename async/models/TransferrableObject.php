<?php

namespace app\async\models;

use app\models\settings\JsonConfigTrait;

abstract class TransferrableObject implements \JsonSerializable
{
    use JsonConfigTrait;
}
