<?php

declare(strict_types=1);

namespace app\models\api\agenda;

use app\models\db\{Consultation, ConsultationAgendaItem};

class AgendaList
{
    public function __construct(
        /** @var AgendaItem[] */
        public array $items,
    ) {
    }

    public static function getItemsFromConsultation(Consultation $consultation): self
    {
        $allItems = $consultation->agendaItems;
        $rootItems = array_values(array_filter($allItems, fn(ConsultationAgendaItem $item) => $item->parentItemId === null));
        usort($rootItems, fn(ConsultationAgendaItem $a, ConsultationAgendaItem $b) => $a->position <=> $b->position);

        return new AgendaList(array_map(
            fn(ConsultationAgendaItem $item) => AgendaItem::fromEntity($item, $allItems),
            $rootItems
        ));
    }
}
