<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\models\db\{Consultation, DebateItem as DebateItemEntity};

class DebateState
{
    public function __construct(
        public ?DebateItem $current = null,
    ) {
    }

    public static function fromConsultation(Consultation $consultation): self
    {
        $current = DebateItemEntity::getCurrentForConsultation($consultation);

        return new self(
            current: ($current ? DebateItem::fromEntity($current) : null),
        );
    }
}
