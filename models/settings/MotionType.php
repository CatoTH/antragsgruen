<?php

namespace app\models\settings;

class MotionType implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $pdfIntroduction = '';
    public ?string $cssIcon = null;
    public string $motionTitleIntro = '';

    public bool $hasProposedProcedure = false;
    public bool $hasResponsibilities = false;
    public bool $twoColMerging = false; // Can only be set manually as of yet
    public bool $commentsRestrictViewToWritables = false;
    public bool $allowAmendmentsToAmendments = false;
    public bool $showProposalsInExports = false;
}
