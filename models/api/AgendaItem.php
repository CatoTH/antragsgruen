<?php

declare(strict_types=1);

namespace app\models\api;

use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;

class AgendaItem
{
    public const TYPE_ITEM = 'item';
    public const TYPE_DATE_SEPARATOR = 'date_separator';

    public ?int $id = null;
    public string $type;
    public ?string $code = null;
    public string $title;
    public ?string $time = null;
    public ?string $date = null; // Only set for type=date_separator

    public AgendaItemSettings $settings;

    /** @var AgendaItem[] */
    public array $children;

    public function addChildren(AgendaItem $item): void
    {
        $this->children[] = $item;
    }

    public function removeChildren(AgendaItem $item): void
    {
        $this->children = array_values(array_filter($this->children, fn (AgendaItem $it) => $it !== $item));
    }

    /**
     * @return AgendaItem[]
     */
    public static function getItemsFromConsultation(Consultation $consultation): array
    {
        $allItems = $consultation->agendaItems;
        $rootItems = array_values(array_filter($allItems, fn (ConsultationAgendaItem $item) => $item->parentItemId === null));
        usort($rootItems, fn (ConsultationAgendaItem $a, ConsultationAgendaItem $b) => $a->position <=> $b->position);

        return array_map(
            fn (ConsultationAgendaItem $item) => self::fromEntity($item, $allItems),
            $rootItems
        );
    }

    /**
     * @param ConsultationAgendaItem[] $allEntities
     */
    private static function fromEntity(ConsultationAgendaItem $entity, array $allEntities): self
    {
        $apiItem = new self();
        $apiItem->id = $entity->id;
        $apiItem->code = ($entity->code === ConsultationAgendaItem::CODE_AUTO || $entity->code === '' ? null : $entity->code);
        $apiItem->title = $entity->title;
        if ($entity->time && preg_match('/^\d{4}-\d{2}-\d{2}$/', $entity->time)) {
            $apiItem->type = self::TYPE_DATE_SEPARATOR;
            $apiItem->date = $entity->time;
        } else {
            $apiItem->type = self::TYPE_ITEM;
            if ($entity->time && preg_match('/^\d{2}:\d{2}$/', $entity->time)) {
                $apiItem->time = $entity->time;
            }
        }
        $apiItem->settings = AgendaItemSettings::fromEntity($entity);

        $childEntities = array_values(array_filter($allEntities, fn (ConsultationAgendaItem $child) => $child->parentItemId === $entity->id));
        usort($childEntities, fn (ConsultationAgendaItem $a, ConsultationAgendaItem $b) => $a->position <=> $b->position);

        $apiItem->children = array_map(
            fn(ConsultationAgendaItem $child) => self::fromEntity($child, $allEntities),
            $childEntities
        );

        return $apiItem;
    }
}
