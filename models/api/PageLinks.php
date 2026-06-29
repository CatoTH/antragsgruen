<?php

declare(strict_types=1);

namespace app\models\api;

class PageLinks
{
    public function __construct(
        public ?int $id = null,
        public ?bool $inMenu = null,
        public ?string $title = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
