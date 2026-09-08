<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingEditable
{
    public function __construct(
        public bool $itemsCanBeAdded,
        public bool $itemsCanBeRemoved,
        public bool $settingsCanBeChanged,
    ) {
    }
}
