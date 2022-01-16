<?php

declare(strict_types=1);

namespace app\models\db;

interface IHasPolicies
{
    public function isInDeadline(string $type): bool;
    public function getDeadlinesByType(string $type): array;
}
