<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionPagination
{
    public function __construct(
        public ?string $prev = null,
        public ?string $next = null,
    ) {
    }
}
