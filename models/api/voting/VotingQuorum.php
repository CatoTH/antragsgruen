<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingQuorum
{
    public function __construct(
        public int $type,
        public int $eligible,
        public ?int $target = null,
        public ?\app\models\api\LocalizedString $targetLabel = null,
    ) {
    }
}
