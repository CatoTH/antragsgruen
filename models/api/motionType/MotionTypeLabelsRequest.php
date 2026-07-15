<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeLabelsRequest
{
    public function __construct(
        public string $singular,
        public string $plural,
        public string $create,
    ) {
    }
}
