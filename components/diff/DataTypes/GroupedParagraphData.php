<?php

namespace app\components\diff\DataTypes;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

class GroupedParagraphData
{
    /** @var int - 0 for "no amendment" */
    public $amendment;

    /** @var string */
    public $text;
}
