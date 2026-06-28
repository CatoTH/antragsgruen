<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeList
{
    public function __construct(
        /** @var MotionType[] */
        public array $items,
    ) {
    }
}
