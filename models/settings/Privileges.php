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

    // View the admin motion list (incl. reading). No extra editing permissions yet.
    public const PRIVILEGE_MOTION_SEE_UNPUBLISHED = 13;

    // Editing statuses, signatures, tags, title. NOT: text, initiators, deleting. BUT: allows everything SCREENING does.
    public const PRIVILEGE_MOTION_STATUS_EDIT = 4;

    // Editing the text. Merging amendments into motions.
    public const PRIVILEGE_MOTION_TEXT_EDIT = 11;

    // Deleting motions / amendments. Moving motions away FROM this consultation.
    public const PRIVILEGE_MOTION_DELETE = 12;

    // Editing the initiators. Creating motions in the name of someone else. Moving motions TO this consultation.
    public const PRIVILEGE_MOTION_INITIATORS = 5;

    // Editing the proposed procedure
    public const PRIVILEGE_CHANGE_PROPOSALS = 7;

    // Editing the editorial texts / progress reports
    public const PRIVILEGE_CHANGE_EDITORIAL = 14;

    /** @var Privileges[] */
    private static array $cachedPrivileges = [];

    public static function getPrivileges(\app\models\db\Consultation $consultation): self
    {
        if (!isset(self::$cachedPrivileges[$consultation->id])) {
            self::$cachedPrivileges[$consultation->id] = new Privileges($consultation);
        }
        return self::$cachedPrivileges[$consultation->id];
    }

    public function __construct(\app\models\db\Consultation $consultation)
    {
        $this->cachedAllPrivileges = [
            // General (non-motion related) privileges
            self::PRIVILEGE_CONSULTATION_SETTINGS => new Privilege(
                self::PRIVILEGE_CONSULTATION_SETTINGS,
                \Yii::t('structure', 'privilege_consettings'),
                false,
                null
            ),
            self::PRIVILEGE_CONTENT_EDIT => new Privilege(
                self::PRIVILEGE_CONTENT_EDIT,
                \Yii::t('structure', 'privilege_content'),
                false,
                null
            ),
            self::PRIVILEGE_SPEECH_QUEUES => new Privilege(
                self::PRIVILEGE_SPEECH_QUEUES,
                \Yii::t('structure', 'privilege_speech'),
                false,
                null
            ),
            self::PRIVILEGE_VOTINGS => new Privilege(
                self::PRIVILEGE_VOTINGS,
                \Yii::t('structure', 'privilege_voting'),
                false,
                null
            ),
            self::PRIVILEGE_SCREENING => new Privilege(
                self::PRIVILEGE_SCREENING,
                \Yii::t('structure', 'privilege_screening'),
                true,
                null
            ),

            // Motion related privileges
            self::PRIVILEGE_MOTION_SEE_UNPUBLISHED => new Privilege(
                self::PRIVILEGE_MOTION_SEE_UNPUBLISHED,
                \Yii::t('structure', 'privilege_motionsee'),
                true,
                null
            ),
            self::PRIVILEGE_MOTION_STATUS_EDIT => new Privilege(
                self::PRIVILEGE_MOTION_STATUS_EDIT,
                \Yii::t('structure', 'privilege_motionstruct'),
                true,
                null
            ),
            self::PRIVILEGE_MOTION_TEXT_EDIT => new Privilege(
                self::PRIVILEGE_MOTION_TEXT_EDIT,
                \Yii::t('structure', 'privilege_motioncontent'),
                true,
                self::PRIVILEGE_MOTION_STATUS_EDIT
            ),
            self::PRIVILEGE_MOTION_INITIATORS => new Privilege(
                self::PRIVILEGE_MOTION_INITIATORS,
                \Yii::t('structure', 'privilege_motionusers'),
                true,
                self::PRIVILEGE_MOTION_STATUS_EDIT
            ),
            self::PRIVILEGE_MOTION_DELETE => new Privilege(
                self::PRIVILEGE_MOTION_DELETE,
                \Yii::t('structure', 'privilege_motiondelete'),
                true,
                null
            ),
            self::PRIVILEGE_CHANGE_PROPOSALS => new Privilege(
                self::PRIVILEGE_CHANGE_PROPOSALS,
                \Yii::t('structure', 'privilege_proposals'),
                true,
                null
            ),
            self::PRIVILEGE_CHANGE_EDITORIAL => new Privilege(
                self::PRIVILEGE_CHANGE_EDITORIAL,
                \Yii::t('structure', 'privilege_progress'),
                true,
                null
            ),
        ];

        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $this->cachedAllPrivileges = $plugin::addCustomPrivileges($consultation, $this->cachedAllPrivileges);
        }

        $this->cachedMotionPrivileges = [];
        $this->cachedNonMotionPrivileges = [];

        foreach ($this->cachedAllPrivileges as $privilege) {
            if ($privilege->motionRestrictable) {
                $this->cachedMotionPrivileges[$privilege->id] = $privilege;
            } else {
                $this->cachedNonMotionPrivileges[$privilege->id] = $privilege;
            }
        }
    }

    /** @var Privilege[] */
    private array $cachedAllPrivileges;

    /** @var Privilege[] */
    private array $cachedNonMotionPrivileges;

    /** @var Privilege[] */
    private array $cachedMotionPrivileges;

    /**
     * @return Privilege[]
     */
    public function getAllPrivileges(): array
    {
        return $this->cachedAllPrivileges;
    }

    /**
     * @return Privilege[]
     */
    public function getNonMotionPrivileges(): array
    {
        return $this->cachedNonMotionPrivileges;
    }

    /**
     * @return Privilege[]
     */
    public function getMotionPrivileges(): array
    {
        return $this->cachedMotionPrivileges;
    }

    public function getPrivilegeDependencies(): array
    {
        $dependencies = [];
        foreach ($this->cachedAllPrivileges as $privilege) {
            if ($privilege->dependentOnId) {
                $dependencies[$privilege->id] = $privilege->dependentOnId;
            }
        }
        return $dependencies;
    }
}
