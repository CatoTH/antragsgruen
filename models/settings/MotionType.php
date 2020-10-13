<?php

namespace app\models\settings;

class MotionType implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var string */
    public $pdfIntroduction   = '';
    public $cssIcon           = '';
    public $motionTitleIntro  = '';
    public $createExplanation = '';

    /** @var bool */
    public $hasProposedProcedure = false;
    /** @var bool */
    public $hasResponsibilities = false;
    /** @var bool */
    public $twoColMerging = false; // Can only be set manually as of yet
    /** @var bool */
    public $hasCreateExplanation = false;
}
