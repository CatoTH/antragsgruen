<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateVotingCreateRequest
{
    public function __construct(
        public ?string $question = null,
    ) {
    }
}
