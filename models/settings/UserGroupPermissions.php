<?php

namespace app\models\settings;

use app\models\db\ConsultationUserGroup;

class UserGroupPermissions
{
    public const PERMISSION_PROPOSED_PROCEDURE = 'proposed-procedure';
    public const PERMISSION_ADMIN_ALL = 'admin-all';
    public const PERMISSION_ADMIN_SPEECH_LIST = 'admin-speech-list';

    private bool $isSiteWide;

    /** @var string[]|null */
    private ?array $defaultPermissions = null;

    public function __construct(bool $isSiteWide)
    {
        $this->isSiteWide = $isSiteWide;
    }

    public static function fromDatabaseString(?string $str, bool $isSiteWide): self
    {
        if ($str === null) {
            return new self($isSiteWide);
        }

        if (strpos($str, '{') === 0) {
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

        return $permissions;
    }

    public static function fromLegacyDatabaseString(?string $str, bool $isSiteWide): self
    {
        $permissions = new self($isSiteWide);
        $permissions->defaultPermissions = explode(',', $str);
        return $permissions;
    }

    public function toDatabaseString(): ?string
    {
        if ($this->defaultPermissions) {
            return json_encode([
                'default_permissions' => $this->defaultPermissions
            ], JSON_THROW_ON_ERROR);
        }

        return null;
    }

    public function toApi(): array
    {
        return $this->defaultPermissions;
    }

    public function containsPrivilege(int $privilege): bool
    {
        if (!$this->defaultPermissions) {
            return false;
        }

        switch ($privilege) {
            // Special case "any": everyone having any kind of special privilege
            case ConsultationUserGroup::PRIVILEGE_ANY:
                return in_array(static::PERMISSION_PROPOSED_PROCEDURE, $this->defaultPermissions, true) ||
                    in_array(static::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true) ||
                    in_array(static::PERMISSION_ADMIN_SPEECH_LIST, $this->defaultPermissions, true);

            // Special case "site admin": has all permissions - for all consultations
            case ConsultationUserGroup::PRIVILEGE_SITE_ADMIN:
                return in_array(static::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true) && $this->isSiteWide;

            // Regular cases
            case ConsultationUserGroup::PRIVILEGE_CONSULTATION_SETTINGS:
            case ConsultationUserGroup::PRIVILEGE_CONTENT_EDIT:
            case ConsultationUserGroup::PRIVILEGE_SCREENING:
            case ConsultationUserGroup::PRIVILEGE_MOTION_STATUS_EDIT:
            case ConsultationUserGroup::PRIVILEGE_MOTION_TEXT_EDIT:
            case ConsultationUserGroup::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS:
            case ConsultationUserGroup::PRIVILEGE_VOTINGS:
                return in_array(static::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case ConsultationUserGroup::PRIVILEGE_CHANGE_PROPOSALS:
                return in_array(static::PERMISSION_PROPOSED_PROCEDURE, $this->defaultPermissions, true) ||
                    in_array(static::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case ConsultationUserGroup::PRIVILEGE_SPEECH_QUEUES:
                return in_array(static::PERMISSION_ADMIN_SPEECH_LIST, $this->defaultPermissions, true) ||
                    in_array(static::PERMISSION_ADMIN_ALL, $this->defaultPermissions, true);
            case ConsultationUserGroup::PRIVILEGE_GLOBAL_USER_ADMIN: // only superadmins are allowed to
            default:
                return false;
        }
    }
}
