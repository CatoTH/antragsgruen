<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\api\AgendaItem;
use app\models\db\{Consultation, ConsultationAgendaItem};
use app\models\exceptions\FormError;

class AgendaSaver
{
    public function __construct(
        private readonly Consultation $consultation,
    ) {
    }

    private function getOrCreateItem(?int $id): ConsultationAgendaItem
    {
        if ($id) {
            $item = ConsultationAgendaItem::findOne(['id' => $id, 'consultationId' => $this->consultation->id]);
            if (!$item) {
                throw new FormError('Inconsistency - did not find given agenda item: ' . $id);
            }
        } else {
            $item = new ConsultationAgendaItem();
            $item->consultationId = $this->consultation->id;
        }

        return $item;
    }

    /**
     * @param AgendaItem[] $apiItems
     */
    public function saveAgendaFromApi(?ConsultationAgendaItem $parentItem, array $apiItems): void
    {
        $position = 0;
        foreach ($apiItems as $apiItem) {
            $dbItem = $this->getOrCreateItem($apiItem->id);

            $dbItem->parentItemId = $parentItem?->id;
            $dbItem->position = $position;
            $dbItem->title = mb_substr($apiItem->title, 0, 250);
            $dbItem->code  = ($apiItem->code !== null ? mb_substr($apiItem->code, 0, 20) : ConsultationAgendaItem::CODE_AUTO);
            /*
             *

            $settings                       = $item->getSettingsObj();
            $settings->inProposedProcedures = (!isset($data['inProposedProcedures']) || $data['inProposedProcedures']);
            $item->setSettingsObj($settings);

            if (isset($data['hasSpeakingList']) && $data['hasSpeakingList']) {
                $item->addSpeakingListIfNotExistant();
            } else {
                $item->removeSpeakingListsIfPossible();
            }

            if (isset($data['time']) && preg_match('/^(?<hour>\d\d):(?<minute>\d\d)(?<ampm> (AM|PM))?$/siu', $data['time'], $matches)) {
                $hour = $matches['hour'];
                if (isset($matches['ampm']) && trim(strtolower($matches['ampm'])) === 'pm') {
                    $hour = (string)((int)$hour + 12);
                }
                $item->time = $hour . ':' . $matches['minute'];
            } else {
                $item->time = null;
            }
            Motion Type
            */

            $dbItem->save();

            $this->saveAgendaFromApi($dbItem, $apiItem->children);

            $dbItem->refresh();

            $position++;
        }
    }
}
