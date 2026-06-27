<?php

declare(strict_types=1);

namespace app\models\api\agenda;

use app\models\db\ConsultationAgendaItem;

class AgendaItem
{
    public function __construct(
        public AgendaItemType $type,
        public string $title,
        public AgendaItemSettings $settings,
        /** @var AgendaItem[] */
        public array $children,
        public ?int $id = null,
        public ?string $code = null,
        public ?string $time = null,
        public ?string $date = null,
    ) {
    }

    /**
     * @param ConsultationAgendaItem[] $allEntities
     */
    public static function fromEntity(ConsultationAgendaItem $entity, array $allEntities): self
    {
        if ($entity->time && preg_match('/^\d{4}-\d{2}-\d{2}$/', $entity->time)) {
            $type = AgendaItemType::DATE_SEPARATOR;
            $date = $entity->time;
            $time = null;
        } else {
            $type = AgendaItemType::ITEM;
            $date = null;
            $time = null;
            if ($entity->time && preg_match('/^\d{2}:\d{2}$/', $entity->time)) {
                $time = $entity->time;
            }
        }

        $childEntities = array_values(array_filter($allEntities, fn (ConsultationAgendaItem $child) => $child->parentItemId === $entity->id));
        usort($childEntities, fn (ConsultationAgendaItem $a, ConsultationAgendaItem $b) => $a->position <=> $b->position);

        return new self(
            type: $type,
            title: $entity->title,
            id: $entity->id,
            code: ($entity->code === ConsultationAgendaItem::CODE_AUTO || $entity->code === '' ? null : $entity->code),
            time: $time,
            date: $date,
            settings: AgendaItemSettings::fromEntity($entity),
            children: array_map(
                fn(ConsultationAgendaItem $child) => self::fromEntity($child, $allEntities),
                $childEntities
            )
        );
    }
}
