<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionUpdateSection
{
    public function __construct(
        public int $sectionId,
        public mixed $data = null,
    ) {
    }
}
