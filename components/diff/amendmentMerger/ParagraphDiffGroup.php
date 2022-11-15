<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\DiffWord;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

class ParagraphDiffGroup
{
    /** @var DiffWord[] */
    public $tokens;

    /** @var bool */
    public $collides;

    /** @var int[] */
    public $collisionIds;

    /** @var int */
    public $firstCollisionPos;

    /** @var int */
    public $lastCollisionPos;
}
