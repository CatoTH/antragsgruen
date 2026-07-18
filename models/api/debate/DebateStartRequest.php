<?php

declare(strict_types=1);

namespace app\models\api\debate;

/**
 * Request body of PUT /rest/{site}/{con}/debate, naming the motion, amendment, or agenda item
 * to be debated from now on. Sent as e.g. {"target_type": "motion", "target_id": 123}.
 */
class DebateStartRequest
{
    public function __construct(
        public DebateItemTargetType $targetType,
        public int $targetId,
    ) {
    }
}
