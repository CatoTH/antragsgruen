<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionLink
{
    public function __construct(
        public ?int $id = null,
        public ?string $agendaItem = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithIntro = null,
        public ?string $titleWithPrefix = null,
        public ?string $initiatorsHtml = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
