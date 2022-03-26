<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    const PUBLIC_NO = 0;
    const PUBLIC_YES = 1;

    /** @var int */
    public $imgMaxWidth = 0;
    /** @var int */
    public $imgMaxHeight = 0;

    /** @var bool */
    public $showInHtml = false; // Used for titles and PDF-alternatives

    /** @var int */
    public $public = MotionSection::PUBLIC_YES;
}
