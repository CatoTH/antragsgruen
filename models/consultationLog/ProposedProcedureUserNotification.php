<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureUserNotification implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $text;
    public int $version;
    public int $procedureId;

    public static function create(string $text, int $version, int $procedureId): ProposedProcedureUserNotification
    {
        $object = new ProposedProcedureUserNotification(null);
        $object->text = $text;
        $object->version = $version;
        $object->procedureId = $procedureId;

        return $object;
    }
}
