<?php

declare(strict_types=1);

namespace app\models\api\debate;

/**
 * One selectable target for the debate moderation dropdowns.
 */
class DebateSelectableItem
{
    public function __construct(
        public DebateItemTargetType $targetType,
        public int $targetId,
        public string $title,
        public ?string $titleWithPrefix = null,
        public ?string $initiatorsHtml = null,
    ) {
    }
}
