<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\api\AgendaItem;
use app\models\exceptions\NotFound;
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

    public function saveAgendaFromApi(array $data): void
    {
        $newIds = $this->saveAgendaFromApiRec(null, $data);
        $this->removeOldAgendaItems($newIds);
    }

    /**
     * @param AgendaItem[] $apiItems
     *
     * @return int[]
     */
    private function saveAgendaFromApiRec(?ConsultationAgendaItem $parentItem, array $apiItems): array
    {
        $newIds = [];

        $position = 0;
        foreach ($apiItems as $apiItem) {
            $dbItem = $this->getOrCreateItem($apiItem->id);

            $dbItem->parentItemId = $parentItem?->id;
            $dbItem->position = $position;
            $dbItem->title = (string) grapheme_substr($apiItem->title, 0, 250);
            $dbItem->code  = ($apiItem->code !== null ? mb_substr($apiItem->code, 0, 20) : ConsultationAgendaItem::CODE_AUTO);

            $settings = $dbItem->getSettingsObj();
            $settings->inProposedProcedures = $apiItem->settings->inProposedProcedures;
            $dbItem->setSettingsObj($settings);

            if ($apiItem->date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $apiItem->date)) {
                $dbItem->time = $apiItem->date;
            } elseif ($apiItem->time && preg_match('/^\d+:\d{2}$/', $apiItem->time)) {
                $dbItem->time = $apiItem->time;
            } else {
                $dbItem->time = null;
            }

            try {
                if (count($apiItem->settings->motionTypes) > 0) {
                    // Hint: for now, we only support one motion type
                    $type = $this->consultation->getMotionType($apiItem->settings->motionTypes[0]); // Throws an exception if not existent
                    $dbItem->motionTypeId = $type->id;
                } else {
                    $dbItem->motionTypeId = null;
                }
            } catch (NotFound) {
                $dbItem->motionTypeId = null;
            }

            $dbItem->save();

            if ($apiItem->settings->hasSpeakingList) {
                $dbItem->addSpeakingListIfNotExistant();
            } else {
                $dbItem->removeSpeakingListsIfPossible();
            }

            $recNewIds = $this->saveAgendaFromApiRec($dbItem, $apiItem->children);
            $newIds = array_merge($newIds, $recNewIds);

            $dbItem->refresh();

            $position++;
            $newIds[] = $dbItem->id;
        }

        return $newIds;
    }

    /**
     * @param int[] $newIds
     */
    private function removeOldAgendaItems(array $newIds): void
    {
        foreach ($this->consultation->agendaItems as $agItem) {
            if (!in_array($agItem->id, $newIds)) {
                foreach ($agItem->speechQueues as $speechQueue) {
                    $agItem->unlink('speechQueues', $speechQueue);
                }
                $agItem->delete();
            }
        }
    }
}
