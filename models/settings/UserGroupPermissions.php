<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\Consultation;

class UserGroupPermissions
{
    public const PERMISSION_PROPOSED_PROCEDURE = 'proposed-procedure';
    public const PERMISSION_ADMIN_ALL = 'admin-all';
    public const PERMISSION_ADMIN_SPEECH_LIST = 'admin-speech-list';

    /** @var string[]|null */
    private ?array $defaultPermissions = null;

    /**
     * Hint: detailed privileges can only be granted on consultation level, not site-wide
     *
     * @var UserGroupPermissionEntry[]|null
     */
    private ?array $privileges = null;

    public function __construct(
        private readonly bool $isSiteWide
    ) {
    }

    public static function fromDatabaseString(?string $str, bool $isSiteWide): self
    {
        if ($str === null) {
            return new self($isSiteWide);
        }

        if (str_starts_with($str, '{')) {
            return self::fromJsonDatabaseString($str, $isSiteWide);
        } else {
            return self::fromLegacyDatabaseString($str, $isSiteWide);
        }
    }

    public static function fromJsonDatabaseString(?string $str, bool $isSiteWide): self
    {
        $permissions = new self($isSiteWide);
        $data = json_decode($str, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['default_permissions'])) {
            $permissions->defaultPermissions = $data['default_permissions'];
        }

        if (isset($data['privileges'])) {
            $permissions->privileges = array_map(function (array $arr): UserGroupPermissionEntry {
                /** @var array{motionTypeId?: int|null, agendaItemId?: int|null, tagId?: int|null, privileges: int[]} $arr */
                return UserGroupPermissionEntry::fromArray($arr);
            }, $data['privileges']);
        }

        return $permissions;
    }

    public static function fromLegacyDatabaseString(?string $str, bool $isSiteWide): self
    {
        $permissions = new self($isSiteWide);
        $permissions->defaultPermissions = $str ? explode(',', $str) : null;

        return $permissions;
    }

    public static function fromApi(Consultation $consultation, array $list): self
    {
        $obj = new self(false);
        $obj->privileges = array_map(function (array $api) use ($consultation): UserGroupPermissionEntry {
            return UserGroupPermissionEntry::fromApi($consultation, $api);
        }, $list);

        return $obj;
    }

    public function toDatabaseString(): ?string
    {
        if ($this->defaultPermissions) {
            return json_encode([
                'default_permissions' => $this->defaultPermissions,
            ], JSON_THROW_ON_ERROR);
        }

        if ($this->privileges) {
            return json_encode([
                'privileges' => array_map(function (UserGroupPermissionEntry $entry): array {
                    return $entry->toArray();
                }, $this->privileges),
            ], JSON_THROW_ON_ERROR);
        }

        return null;
    }

    public function toApi(?Consultation $consultation): array
    {
        $apiPrivileges = null;
        if ($this->privileges && $consultation) {
            $apiPrivileges = array_map(function (UserGroupPermissionEntry $arr) use ($consultation): array {
                return $arr->toApi($consultation);
            }, $this->privileges);
        }

        return [
            'default_permissions' => $this->defaultPermissions,
            'privileges' => $apiPrivileges,
        ];
    }

    /**
     * @param array{tags: array<int|string, int>, tags: array<int|string, int>, agenda: array<int|string, int>, motionTypes: array<int|string, int>} $idMapping
     */
    public function cloneWithReplacedIds(array $idMapping): self
    {
        $cloned = new self($this->isSiteWide);
        $cloned->defaultPermissions = $this->defaultPermissions;
        if ($this->privileges) {
            $cloned->privileges = array_map(
                fn (UserGroupPermissionEntry $entry): UserGroupPermissionEntry => $entry->cloneWithReplacedIds($idMapping),
                $this->privileges
            );
        }

        return $cloned;
    }

    public function containsPrivilege(int $privilege, ?PrivilegeQueryContext $context): bool
    {
        foreach ($this->privileges ?? [] as $priv) {
            if ($priv->containsPrivilege($privilege, $context)) {
                return true;
            }
        }
        if (!$this->defaultPermissions) {
            return false;
        }

        switch ($privilege) {
            // Special case "any": everyone having any kind of special privilege
            case Privileges::PRIVILEGE_ANY:
                return in_array(self::PERMISSION_PROPOSED_PROCEDURE, $this->defaultPermissions, true) ||
                    in_array(self::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true) ||
                    in_array(self::PERMISSION_ADMIN_SPEECH_LIST, $this->defaultPermissions, true);

            // Special case "site admin": has all permissions - for all consultations
            case Privileges::PRIVILEGE_SITE_ADMIN:
                return in_array(self::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true) && $this->isSiteWide;

            // Regular cases
            case Privileges::PRIVILEGE_CONSULTATION_SETTINGS:
            case Privileges::PRIVILEGE_CONTENT_EDIT:
            case Privileges::PRIVILEGE_SCREENING:
            case Privileges::PRIVILEGE_MOTION_STATUS_EDIT:
            case Privileges::PRIVILEGE_MOTION_TEXT_EDIT:
            case Privileges::PRIVILEGE_MOTION_DELETE:
            case Privileges::PRIVILEGE_MOTION_INITIATORS:
            case Privileges::PRIVILEGE_VOTINGS:
            case Privileges::PRIVILEGE_CHANGE_EDITORIAL:
                return in_array(self::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case Privileges::PRIVILEGE_CHANGE_PROPOSALS:
                return in_array(self::PERMISSION_PROPOSED_PROCEDURE, $this->defaultPermissions, true) ||
                    in_array(self::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case Privileges::PRIVILEGE_SPEECH_QUEUES:
                return in_array(self::PERMISSION_ADMIN_SPEECH_LIST, $this->defaultPermissions, true) ||
                    in_array(self::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case Privileges::PRIVILEGE_GLOBAL_USER_ADMIN: // only superadmins are allowed to
            default:
                return false;
        }
    }
}
