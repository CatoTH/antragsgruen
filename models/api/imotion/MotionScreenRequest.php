<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionScreenRequest
{
    public function __construct(
        public string $version,
        public ?string $titlePrefix = null,
    ) {
    }
}
