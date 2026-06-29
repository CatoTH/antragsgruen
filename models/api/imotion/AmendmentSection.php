<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class AmendmentSection
{
    public function __construct(
        public ?AmendmentSectionType $type = null,
        public ?string $title = null,
        public ?string $html = null,
    ) {
    }
}
