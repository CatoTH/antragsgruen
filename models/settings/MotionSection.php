<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    public const PUBLIC_NO = 0;
    public const PUBLIC_YES = 1;

    public int $imgMaxWidth = 0;
    public int $imgMaxHeight = 0;

    public bool $showInHtml = false; // Used for titles and PDF-alternatives
    public int $public = MotionSection::PUBLIC_YES;
    public bool $isRtl = false; // If true, the text is to be shown in Right-to-Left direction

    public ?string $explanationHtml = null; // shown above the input field
}
