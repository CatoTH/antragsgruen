<?php

declare(strict_types=1);

namespace app\models\api\errors;

class ErrorApiDisabled
{
    public function __construct(
        public ?bool $success = null,
        public ?string $error = null,
    ) {
    }
}
