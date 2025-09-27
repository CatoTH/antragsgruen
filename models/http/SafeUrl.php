<?php

declare(strict_types=1);

namespace app\models\http;

class SafeUrl
{
    public function __construct(
        private readonly string $url,
    ) {}

    public function getUrl(): string
    {
        return $this->url;
    }
}
