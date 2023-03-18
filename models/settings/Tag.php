<?php

declare(strict_types=1);

namespace app\models\settings;

class Tag implements \JsonSerializable
{
    use JsonConfigTrait;

    public ?string $motionPrefix; // If set, it takes precedence over ConsultationMotionType::motionPrefix
}
