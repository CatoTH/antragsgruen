<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class AmendmentScreenRequest
{
    public function __construct(
        public ?string $titlePrefix = null,
    ) {
    }
}
