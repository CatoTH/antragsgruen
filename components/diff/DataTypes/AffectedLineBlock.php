<?php

namespace app\components\diff\DataTypes;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

class AffectedLineBlock
{
    public string $text;
    public int $lineFrom;
    public int $lineTo;
}
