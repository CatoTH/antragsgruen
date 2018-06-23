<?php

namespace app\models\settings;

class MotionType
{
    use JsonConfigTrait;

    /** @var string */
    public $pdfIntroduction = '';
    public $cssIcon = '';

    /** @var bool */
    public $layoutTwoCols = false;
}
