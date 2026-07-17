<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeAmendmentInitiatorSettingsUpdateRequest
{
    public function __construct(
        public bool $sameAsMotion,
        public ?MotionTypeInitiatorSettingsUpdateRequest $settings = null,
        public ?int $maxPdfSupporters = null,
    ) {
    }
}
