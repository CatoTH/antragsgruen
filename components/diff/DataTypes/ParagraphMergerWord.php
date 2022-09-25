<?php

namespace app\components\diff\DataTypes;

// Pure data objects. They are more performant than using array/hashes.
// Also, skipping the constructor and manually assigning the properties seems to increase performance a bit

use app\components\diff\amendmentMerger\ParagraphDiffGroup;

class ParagraphMergerWord
{
    public string $orig = '';
    public ?string $modification = null;
    public ?int $modifiedBy = null;

    /** @var null|ParagraphDiffGroup[] */
    public ?array $prependCollisionGroups = null;

    /** @var null|ParagraphDiffGroup[] */
    public ?array $appendCollisionGroups = null;
}
