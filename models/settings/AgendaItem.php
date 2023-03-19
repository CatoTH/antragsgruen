<?php

declare(strict_types=1);

namespace app\models\settings;

class AgendaItem implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $inProposedProcedures = true;
    public string $description = '';
}
