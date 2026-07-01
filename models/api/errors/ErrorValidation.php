<?php

declare(strict_types=1);

namespace app\models\api\errors;

class ErrorValidation
{
    /** @param string[] $errors */

    public function __construct(
        public ?bool $success = null,
        /** @var string[]|null */
        public ?array $errors = null,
    ) {
    }
}
