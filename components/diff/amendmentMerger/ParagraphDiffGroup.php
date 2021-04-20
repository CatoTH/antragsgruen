<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\DiffWord;

class ParagraphDiffGroup
{
    /** @var DiffWord[] */
    public $tokens;

    /** @var bool */
    public $collides;

    /** @var int[] */
    public $collisionIds;
}
