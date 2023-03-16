<?php

namespace app\models\settings;

class AgendaItem implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $inProposedProcedures = true;
    public string $description = '';
    public ?string $motionPrefix; // If set, it takes precedence over ConsultationMotionType::motionPrefix
}
