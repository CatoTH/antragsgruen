<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int */
    public $imgMaxWidth = 0;
    public $imgMaxHeight = 0;
}
