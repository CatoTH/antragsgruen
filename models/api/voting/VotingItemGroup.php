<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingItemGroup
{
    public function __construct(
        public string $id,
        /** @var VotingItemRef[] */
        public array $items,
        public ?string $name = null,
        public ?VotingResults $results = null,
        /** @var VotingSingleVote[]|null */
        public ?array $singleVotes = null,
    ) {
    }
}
