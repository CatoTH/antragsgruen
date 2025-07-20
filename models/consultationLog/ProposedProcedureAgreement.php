<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureAgreement implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $byUser;
    public int $version;
    public int $proposalId;
    public ?string $comment;

    public static function create(bool $byUser, int $version, int $proposalId, ?string $comment): ProposedProcedureAgreement
    {
        $object = new self(null);
        $object->byUser = $byUser;
        $object->version = $version;
        $object->proposalId = $proposalId;
        $object->comment = $comment;

        return $object;
    }
}
