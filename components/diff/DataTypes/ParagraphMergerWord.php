<?php

namespace app\components\diff\DataTypes;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

class ParagraphMergerWord
{
    /** @var string */
    public $orig = '';

    /** @var null|string */
    public $modification = null;

    /** @var null|int */
    public $modifiedBy = null;
}
