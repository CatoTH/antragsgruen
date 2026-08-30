<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingItem
{
    public function __construct(
        public VotingItemType $type,
        public int $id,
        public string $groupId,
        public string $titleWithPrefix,
        public ?string $prefix = null,
        public ?string $initiatorsHtml = null,
        public ?string $urlHtml = null,
        public ?string $urlJson = null,
        public ?string $procedureHtml = null,
        public ?VotingItemResult $result = null,
    ) {
    }
}
