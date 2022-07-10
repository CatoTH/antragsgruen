<?php

namespace app\models\settings;

class AgendaItem implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $inProposedProcedures = true;
    public string $description = '';
}
