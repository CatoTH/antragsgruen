<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingItem
{
    public function __construct(
        public VotingItemType $type,
        public int $id,
        public string $groupId,
        public \app\models\api\LocalizedString $titleWithPrefix,
        public ?string $prefix = null,
        public ?\app\models\api\LocalizedString $initiatorsHtml = null,
        public ?string $urlHtml = null,
        public ?string $urlJson = null,
        public ?\app\models\api\LocalizedString $procedureHtml = null,
        public ?VotingItemResult $result = null,
    ) {
    }
}
