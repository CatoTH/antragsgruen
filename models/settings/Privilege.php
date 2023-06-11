<?php

declare(strict_types=1);

namespace app\models\settings;

class Privilege
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $motionRestrictable,
        public ?int $dependentOnId
    ) {
    }
}
