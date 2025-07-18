<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureAgreement implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $byUser;
    public int $procedureId;

    public function __construct(bool $byUser, int $procedureId)
    {
        $this->byUser = $byUser;
        $this->procedureId = $procedureId;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
