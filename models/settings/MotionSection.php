<?php

namespace app\models\settings;

class MotionSection implements \JsonSerializable
{
    use JsonConfigTrait;

    const PUBLIC_NO = 0;
    const PUBLIC_YES = 1;

    public int $imgMaxWidth = 0;
    public int $imgMaxHeight = 0;

    public bool $showInHtml = false; // Used for titles and PDF-alternatives
    public int $public = MotionSection::PUBLIC_YES;
}
