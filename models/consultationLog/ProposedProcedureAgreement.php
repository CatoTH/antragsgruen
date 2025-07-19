<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureAgreement implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $byUser;
    public int $version;
    public int $procedureId;

    public static function create(bool $byUser, int $version, int $procedureId): ProposedProcedureAgreement
    {
        $object = new self(null);
        $object->byUser = $byUser;
        $object->version = $version;
        $object->procedureId = $procedureId;

        return $object;
    }
}
