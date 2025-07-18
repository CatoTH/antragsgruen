<?php

declare(strict_types=1);

namespace app\models\consultationLog;

use app\models\settings\JsonConfigTrait;

class ProposedProcedureUserNotification implements \JsonSerializable
{
    use JsonConfigTrait;

    public string $text;
    public int $procedureId;

    public function __construct(string $text, int $procedureId)
    {
        $this->text = $text;
        $this->procedureId = $procedureId;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
