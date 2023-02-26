<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\Consultation;
use app\models\exceptions\NotFound;

class UserGroupPermissionEntry
{
    private ?int $motionTypeId = null;
    private ?int $agendaItemId = null;
    private ?int $tagId = null;

    /** @var int[] */
    private array $privileges;

    public static function fromArray(array $data): self
    {
        $obj = new self();
        $obj->motionTypeId = $data['motionTypeId'] ?? null;
        $obj->agendaItemId = $data['agendaItemId'] ?? null;
        $obj->tagId = $data['tagId'] ?? null;
        $obj->privileges = $data['privileges'];

        return $obj;
    }

    public function toArray(): array
    {
        return [
            'motionTypeId' => $this->motionTypeId,
            'agendaItemId' => $this->agendaItemId,
            'tagId' => $this->tagId,
            'privileges' => $this->privileges,
        ];
    }

    public function toApi(Consultation $consultation): array {
        $tag = null;
        if ($this->tagId && $tagDb = $consultation->getTagById($this->tagId)) {
            $tag = [
                'id' => $tagDb->id,
                'title' => $tagDb->title,
            ];
        }

        $motionType = null;
        try {
            if ($this->motionTypeId) {
                $motionTypeDb = $consultation->getMotionType($this->motionTypeId);
                $motionType = [
                    'id' => $motionTypeDb->id,
                    'title' => $motionTypeDb->titlePlural,
                ];
            }
        } catch (NotFound $e) {}

        $agendaItem = null;
        if ($this->agendaItemId && $agendaItemDb = $consultation->getAgendaItem($this->agendaItemId)) {
            $agendaItem = [
                'id' => $agendaItemDb->id,
                'title' => $agendaItemDb->title,
            ];
        }

        return [
            'motionType' => $motionType,
            'agendaItem' => $agendaItem,
            'tag' => $tag,
            'privileges' => $this->privileges,
        ];
    }
}
