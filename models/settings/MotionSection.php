<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var int */
    public $imgMaxWidth = 0;
    public $imgMaxHeight = 0;

    /** @var bool */
    public $showInHtml = false; // Used for titles and PDF-alternatives
}
