<?php

namespace app\models\settings;

class AgendaItem implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var bool */
    public $inProposedProcedures = true;

    /** @var string */
    public $description = '';
}
