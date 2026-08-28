<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateItemSpeechQueue
{
    public function __construct(
        public int $id,
        public bool $isActive,
        public \app\models\api\LocalizedString $title,
    ) {
    }
}
