<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeDeadlineEntry
{
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?string $title = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            start: isset($data['start']) ? (new \DateTime($data['start']))->format('c') : null,
            end: isset($data['end']) ? (new \DateTime($data['end']))->format('c') : null,
            title: $data['title'] ?? null,
        );
    }

    /** @return self[] */
    public static function fromDeadlineType(\app\models\db\ConsultationMotionType $motionType, string $deadlineType): array
    {
        return array_map(
            fn(array $d) => self::fromArray($d),
            $motionType->getDeadlinesByType($deadlineType)
        );
    }
}
