<?php

namespace app\components\diff\DataTypes;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

class DiffWord
{
    public string $word = '';
    public string $diff = '';
    public ?int $amendmentId = null;
}
