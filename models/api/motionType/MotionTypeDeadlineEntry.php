<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeDeadlineEntry
{
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?string $title = null,
    ) {
    }
}
