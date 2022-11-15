<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\DiffWord;

class ParagraphDiff
{
    public int $firstDiff;
    public int $amendment;

    /** @var DiffWord[] */
    public array $diff;

    public function __construct(int $amendment, int $firstDiff, array $diff)
    {
        $this->firstDiff = $firstDiff;
        $this->amendment = $amendment;
        $this->diff      = $diff;
    }
}
