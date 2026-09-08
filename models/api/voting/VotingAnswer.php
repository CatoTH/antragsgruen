<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingAnswer
{
    public function __construct(
        public string $apiId,
        public \app\models\api\LocalizedString $title,
        public ?VotingItemResult $result = null,
    ) {
    }
}
