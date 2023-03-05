<?php

declare(strict_types=1);

namespace app\models\settings;

class Privileges
{
    public const PRIVILEGE_ANY = 0;  // SPECIAL CASE: refers to "any" other privilege mentioned below
    public const PRIVILEGE_CONSULTATION_SETTINGS = 1;
    public const PRIVILEGE_CONTENT_EDIT = 2;  // Editing pages, uploaded documents (not motions), agenda
    public const PRIVILEGE_SPEECH_QUEUES = 8;
    public const PRIVILEGE_VOTINGS = 9;
    public const PRIVILEGE_SITE_ADMIN = 6;  // SPECIAL CASE: gives all permissions to all consultations of the site
    public const PRIVILEGE_GLOBAL_USER_ADMIN = 10; // Editing user data, not only groups

    // Motion/Amendment-related permissions. These permissions can be restricted to only a part of the motions / amendments.
    public const PRIVILEGE_SCREENING = 3;
    public const PRIVILEGE_MOTION_STATUS_EDIT = 4;  // Editing statuses, signatures, tags, title. NOT: text, initiators, deleting
    public const PRIVILEGE_MOTION_TEXT_EDIT = 11; // Editing the text and the initiators. Deleting motions / amendments
    public const PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS = 5;
    public const PRIVILEGE_CHANGE_PROPOSALS = 7;  // Editing the proposed procedure

    private static ?int $cachedConsultationId = null;
    private static ?self $cachedPrivileges = null;

    public static function getPrivileges(\app\models\db\Consultation $consultation): self
    {
        if (self::$cachedConsultationId !== $consultation->id) {
            self::$cachedConsultationId = $consultation->id;
            self::$cachedPrivileges = new self();
        }
        return self::$cachedPrivileges;
    }

    /** @var Privilege[]|null */
    private ?array $cachedNonMotionPrivileges = null;

    /** @var Privilege[]|null */
    private ?array $cachedMotionPrivileges = null;

    /**
     * @return Privilege[]
     */
    public function getNonMotionPrivileges(): array
    {
        if ($this->cachedNonMotionPrivileges === null) {
            $this->cachedNonMotionPrivileges = [
                self::PRIVILEGE_CONSULTATION_SETTINGS => new Privilege(
                    self::PRIVILEGE_CONSULTATION_SETTINGS,
                    'Veranstaltungseinstellungen bearbeiten'
                ),
                self::PRIVILEGE_CONTENT_EDIT => new Privilege(
                    self::PRIVILEGE_CONTENT_EDIT,
                    'Redaktionelle Texte bearbeiten'
                ),
                self::PRIVILEGE_SPEECH_QUEUES => new Privilege(
                    self::PRIVILEGE_SPEECH_QUEUES,
                    'Redelisten bearbeiten'
                ),
                self::PRIVILEGE_VOTINGS => new Privilege(
                    self::PRIVILEGE_VOTINGS,
                    'Abstimmungen bearbeiten'
                ),
            ];
        }
        return $this->cachedNonMotionPrivileges;
    }

    /**
     * @return Privilege[]
     */
    public function getMotionPrivileges(): array
    {
        if ($this->cachedMotionPrivileges === null) {
            $this->cachedMotionPrivileges = [
                self::PRIVILEGE_SCREENING => new Privilege(
                    self::PRIVILEGE_SCREENING,
                    'Freischalten'
                ),
                self::PRIVILEGE_MOTION_STATUS_EDIT => new Privilege(
                    self::PRIVILEGE_MOTION_STATUS_EDIT,
                    'Rahmendaten bearbeiten'
                ),
                self::PRIVILEGE_MOTION_TEXT_EDIT => new Privilege(
                    self::PRIVILEGE_MOTION_TEXT_EDIT,
                    'Inhalte/Texte bearbeiten'
                ),
                self::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS => new Privilege(
                    self::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS,
                    'Im Namen Anderer stellen'
                ),
                self::PRIVILEGE_CHANGE_PROPOSALS => new Privilege(
                    self::PRIVILEGE_CHANGE_PROPOSALS,
                    'Verfahrensvorschläge bearbeiten'
                ),
            ];
        }
        return $this->cachedMotionPrivileges;
    }
}
