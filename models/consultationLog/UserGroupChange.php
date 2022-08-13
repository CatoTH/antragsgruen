<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class UserGroupChange implements \JsonSerializable
{
    use JsonConfigTrait;

    public int $byAdminId;
    public string $byAdminAuth;

    public static function create(int $byAdminId, string $byAdminAuth): self
    {
        $object = new self(null);
        $object->byAdminId = $byAdminId;
        $object->byAdminAuth = $byAdminAuth;

        return $object;
    }
}
