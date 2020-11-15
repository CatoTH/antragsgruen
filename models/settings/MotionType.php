<?php

namespace app\models\settings;

class MotionType implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $pdfIntroduction   = '';
    public $cssIcon           = '';
    public $motionTitleIntro  = '';

    /** @var bool */
    public $hasProposedProcedure = false;
    /** @var bool */
    public $hasResponsibilities = false;
    /** @var bool */
    public $twoColMerging = false; // Can only be set manually as of yet
}
