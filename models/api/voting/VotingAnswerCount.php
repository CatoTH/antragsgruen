<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingAnswerCount
{
    public function __construct(
        public string $answer,
        public int $votes,
    ) {
    }
}
