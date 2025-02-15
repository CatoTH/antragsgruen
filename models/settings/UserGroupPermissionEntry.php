<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\Consultation;
use app\models\exceptions\{FormError, NotFound};

class UserGroupPermissionEntry
{
    private ?int $motionTypeId = null;
    private ?int $agendaItemId = null;
    private ?int $tagId = null;

    /** @var int[] */
    private array $privileges;

    /**
     * @param array{motionTypeId?: int|null, agendaItemId?: int|null, tagId?: int|null, privileges: int[]} $data
     */
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

    /**
     * @param array{tags: array<int|string, int>, tags: array<int|string, int>, agenda: array<int|string, int>, motionTypes: array<int|string, int>} $idMapping
     */
    public function cloneWithReplacedIds(array $idMapping): self
    {
        $motionTypeId = ($this->motionTypeId !== null && isset($idMapping['motionTypes'][$this->motionTypeId]) ? $idMapping['motionTypes'][$this->motionTypeId] : null);
        $agendaItemId = ($this->agendaItemId !== null && isset($idMapping['agenda'][$this->agendaItemId]) ? $idMapping['agenda'][$this->agendaItemId] : null);
        $tagId = ($this->tagId !== null && isset($idMapping['tags'][$this->tagId]) ? $idMapping['tags'][$this->tagId] : null);

        return self::fromArray([
            'motionTypeId' => $motionTypeId,
            'agendaItemId' => $agendaItemId,
            'tagId' => $tagId,
            'privileges' => $this->privileges,
        ]);
    }

    /**
     * @throws FormError
     * @throws NotFound
     */
    public static function fromApi(Consultation $consultation, array $api): self
    {
        $perm = new UserGroupPermissionEntry();

        if (isset($api['agendaItem'])) {
            $agendaItem = $consultation->getAgendaItem($api['agendaItem']['id']);
            if (!$agendaItem) {
                throw new FormError('Agenda item not found: ' . $api['agendaItem']['id']);
            }
            $perm->agendaItemId = $agendaItem->id;
        }

        if (isset($api['tag'])) {
            $tag = $consultation->getTagById($api['tag']['id']);
            if (!$tag) {
                throw new FormError('Tag not found: ' . $api['tag']['id']);
            }
            $perm->tagId = $tag->id;
        }

        if (isset($api['motionType'])) {
            $motionType = $consultation->getMotionType($api['motionType']['id']);
            $perm->motionTypeId = $motionType->id;
        }

        $nonMotionPrivileges = Privileges::getPrivileges($consultation)->getNonMotionPrivileges();
        $motionPrivileges = Privileges::getPrivileges($consultation)->getMotionPrivileges();
        foreach ($api['privileges'] as $privId) {
            $privId = intval($privId);

            // Restricted privileges only apply to motion privileges
            if (isset($nonMotionPrivileges[$privId]) && ($perm->agendaItemId || $perm->tagId || $perm->motionTypeId)) {
                throw new FormError('Cannot set privilege ' . $privId . ' to restricted');
            }

            if (!isset($nonMotionPrivileges[$privId]) && !isset($motionPrivileges[$privId])) {
                throw new FormError('Unknown privilege: ' . $privId);
            }

            $perm->privileges[] = $privId;
        }

        return $perm;
    }

    public function toApi(Consultation $consultation): array
    {
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

    public function containsPrivilege(int $privilege, ?PrivilegeQueryContext $context): bool
    {
        if ($privilege === Privileges::PRIVILEGE_ANY) {
            return count($this->privileges) > 0;
        }

        if ($this->tagId !== null) {
            if ($context && $context->matchesTagId($this->tagId)) {
                return in_array($privilege, $this->privileges, true);
            } else {
                return false;
            }
        }

        if ($this->agendaItemId !== null) {
            if ($context && $context->matchesAgendaItemId($this->agendaItemId)) {
                return in_array($privilege, $this->privileges, true);
            } else {
                return false;
            }
        }

        if ($this->motionTypeId !== null) {
            if ($context && $context->matchesMotionTypeId($this->motionTypeId)) {
                return in_array($privilege, $this->privileges, true);
            } else {
                return false;
            }
        }

        return in_array($privilege, $this->privileges, true);
    }
}
