<?php

namespace app\components\diff\amendmentMerger;

class ParagraphDiff
{
    /** @var int */
    public $firstDiff;

    /** @var int */
    public $amendment;

    /** @var array */
    public $diff;

    public function __construct(int $amendment, int $firstDiff, array $diff)
    {
        $this->firstDiff = $firstDiff;
        $this->amendment = $amendment;
        $this->diff      = $diff;
    }
}
