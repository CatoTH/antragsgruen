<?php

declare(strict_types=1);

namespace app\models\api\consultation;

class ConsultationLink
{
    public function __construct(
        public ?string $title = null,
        public ?string $titleShort = null,
        public ?string $datePublished = null,
        public ?string $urlPath = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
