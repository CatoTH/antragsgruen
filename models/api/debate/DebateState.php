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
        if ($current && !$current->isTargetVisible()) {
            // The debated item was deleted or hidden without the debate being ended (which normally
            // happens right away). It must not be described to anyone - this state is served to
            // anonymous visitors and broadcast to every subscriber of the live channel alike.
            $current = null;
        }

        return new self(
            current: ($current ? DebateItem::fromEntity($current) : null),
        );
    }
}
