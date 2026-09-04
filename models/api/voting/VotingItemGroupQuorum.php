<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingItemGroupQuorum
{
    public function __construct(
        public ?int $votes = null,
        public ?\app\models\api\LocalizedString $currentLabel = null,
    ) {
    }
}
