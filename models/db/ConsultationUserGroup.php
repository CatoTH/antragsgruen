<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\{AntragsgruenApp, Site as SiteSettings};
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int|null $id
 * @property string|null $externalId
 * @property int|null $templateId
 * @property string $title
 * @property int|null $consultationId
 * @property int|null $siteId
 * @property int $selectable
 * @property string $permissions
 *
 * @property Consultation|null $consultation
 * @property Site|null $site
 * @property User[] $users
 */
class ConsultationUserGroup extends ActiveRecord
{
    public const PERMISSION_PROPOSED_PROCEDURE = 'proposed-procedure';
    public const PERMISSION_ADMIN_ALL = 'admin-all';
    public const PERMISSION_ADMIN_SPEECH_LIST = 'admin-speech-list';

    // Hint: privileges are mostly grouped into the permissions above;
    // "Any" and "Site admin" have special semantics
    public const PRIVILEGE_ANY                       = 0;
    public const PRIVILEGE_CONSULTATION_SETTINGS     = 1;
    public const PRIVILEGE_CONTENT_EDIT              = 2;
    public const PRIVILEGE_SCREENING                 = 3;
    public const PRIVILEGE_MOTION_EDIT               = 4;
    public const PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS = 5;
    public const PRIVILEGE_SITE_ADMIN                = 6;
    public const PRIVILEGE_CHANGE_PROPOSALS          = 7;
    public const PRIVILEGE_SPEECH_QUEUES             = 8;
    public const PRIVILEGE_VOTINGS                   = 9;

    public const TEMPLATE_SITE_ADMIN = 1;
    public const TEMPLATE_CONSULTATION_ADMIN = 2;
    public const TEMPLATE_PROPOSED_PROCEDURE = 3;
    public const TEMPLATE_PARTICIPANT = 4;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationUserGroup';
    }

    public function getConsultation(): ActiveQuery
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'userId'])->viaTable('userGroup', ['groupId' => 'id'])
                    ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    public static function consultationHasLoadableUserGroups(Consultation $consultation): bool
    {
        return in_array(SiteSettings::LOGIN_GRUENES_NETZ, $consultation->site->getSettings()->loginMethods);
    }

    /**
     * @return ConsultationUserGroup[]
     */
    public static function findBySearchQuery(Consultation $consultation, string $queryString): array
    {
        if (!self::consultationHasLoadableUserGroups($consultation)) {
            return [];
        }

        $allowedExternalPatterns = ['or'];
        if (in_array(SiteSettings::LOGIN_GRUENES_NETZ, $consultation->site->getSettings()->loginMethods)) {
            $allowedExternalPatterns[] = ['like', 'externalId', 'gruenesnetz:%', false];
        }

        /** @var ConsultationUserGroup[] $groups */
        $groups = ConsultationUserGroup::find()
            ->where([
                'and',
                ['siteId' => null],
                $allowedExternalPatterns,
                ['like', 'title', explode(" ", $queryString)],
            ])
            ->orderBy('title')
            ->limit(100)
            ->all();

        return array_values(array_filter($groups, function (ConsultationUserGroup $group) use ($consultation): bool {
            return $group->mayBeUsedForConsultation($consultation);
        }));
    }

    public static function findByConsultation(Consultation $consultation, array $additionalIds = []): array
    {
        /** @var ConsultationUserGroup[] $groups */
        $groups = ConsultationUserGroup::find()
            ->where([
                'or',
                ['siteId' => $consultation->siteId],
                ['consultationId' => $consultation->id],
                ['in', 'id', $additionalIds],
            ])
            ->all();

        return array_values(array_filter($groups, function (ConsultationUserGroup $group) use ($consultation): bool {
            return $group->mayBeUsedForConsultation($consultation);
        }));
    }

    public static function loadGroupsByIdForConsultation(Consultation $consultation, array $groupIds): array
    {
        $groups = [];
        foreach ($groupIds as $groupId) {
            $group = ConsultationUserGroup::findOne(intval($groupId));
            if ($group && $group->mayBeUsedForConsultation($consultation)) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    // Hint: this method can be used if the group assignment of all users need to be evaluated in a call.
    // Iterating over the userIds attached to a user group is faster than over the groupIds of a user,
    // as there are way more users, and we would need to perform more queries that way.
    // Note that this method should only be used for read-only operations, as the cache is not flushed yet.
    /** @var null|int[] */
    private $userIdCache = null;
    public function getUserIds(): array
    {
        if ($this->userIdCache === null) {
            $tableName = AntragsgruenApp::getInstance()->tablePrefix . 'userGroup';
            $rows = (new \yii\db\Query())
                ->select(['userId'])
                ->from($tableName)
                ->where(['groupId' => $this->id])
                ->all();
            $this->userIdCache = [];
            foreach ($rows as $row) {
                $this->userIdCache[] = intval($row['userId']);
            }
        }
        return $this->userIdCache;
    }

    /**
     * @return User[]
     */
    public function getUsersCached(): array
    {
        $users = [];
        foreach ($this->getUserIds() as $userId) {
            $users[] = User::getCachedUser($userId);
        }
        return $users;
    }

    /**
     * @param string[] $permission
     */
    public function setPermissions(array $permission): void
    {
        $this->permissions = implode(',', $permission);
    }

    public function getPermissions(): array
    {
        if ($this->permissions) {
            return explode(',', $this->permissions);
        } else {
            return [];
        }
    }

    public function containsPrivilege(int $privilege): bool
    {
        $permission = $this->getPermissions();
        switch ($privilege) {
            // Special case "any": everyone having any kind of special privilege
            case static::PRIVILEGE_ANY:
                return in_array(static::PERMISSION_PROPOSED_PROCEDURE, $permission, true) ||
                       in_array(static::PERMISSION_ADMIN_ALL, $permission, true) ||
                       in_array(static::PERMISSION_ADMIN_SPEECH_LIST, $permission, true);

            // Special case "site admin": has all permissions - for all consultations
            case static::PRIVILEGE_SITE_ADMIN:
                return in_array(static::PERMISSION_ADMIN_ALL, $permission, true) &&
                       $this->consultationId === null;

            // Regular cases
            case static::PRIVILEGE_CONSULTATION_SETTINGS:
            case static::PRIVILEGE_CONTENT_EDIT:
            case static::PRIVILEGE_SCREENING:
            case static::PRIVILEGE_MOTION_EDIT:
            case static::PRIVILEGE_CREATE_MOTIONS_FOR_OTHERS:
            case static::PRIVILEGE_VOTINGS:
                return in_array(static::PERMISSION_ADMIN_ALL, $permission, true);
            case static::PRIVILEGE_CHANGE_PROPOSALS:
                return in_array(static::PERMISSION_PROPOSED_PROCEDURE, $permission, true) ||
                       in_array(static::PERMISSION_ADMIN_ALL, $permission, true);
            case static::PRIVILEGE_SPEECH_QUEUES:
                return in_array(static::PERMISSION_ADMIN_SPEECH_LIST, $permission, true) ||
                       in_array(static::PERMISSION_ADMIN_ALL, $permission, true);
            default:
                return false;
        }
    }

    public function getNormalizedTitle(): string
    {
        switch ($this->templateId) {
            case static::TEMPLATE_SITE_ADMIN:
                return \Yii::t('user', 'group_template_siteadmin');
            case static::TEMPLATE_CONSULTATION_ADMIN:
                return \Yii::t('user', 'group_template_consultationadmin');
            case static::TEMPLATE_PROPOSED_PROCEDURE:
                return \Yii::t('user', 'group_template_proposed');
            case static::TEMPLATE_PARTICIPANT:
                return \Yii::t('user', 'group_template_participant');
            default:
                return $this->title;
        }
    }

    public function getNormalizedDescription(): ?string
    {
        switch ($this->templateId) {
            case static::TEMPLATE_SITE_ADMIN:
                return \Yii::t('user', 'group_template_siteadmin_h');
            case static::TEMPLATE_CONSULTATION_ADMIN:
                return \Yii::t('user', 'group_template_consultationadmin_h');
            case static::TEMPLATE_PROPOSED_PROCEDURE:
                return \Yii::t('user', 'group_template_proposed_h');
            case static::TEMPLATE_PARTICIPANT:
                return \Yii::t('user', 'group_template_participant_h');
            default:
                return null;
        }
    }

    public function addUser(User $user): void
    {
        $this->link('users', $user);
    }

    public function hasUser(User $user): bool
    {
        foreach ($this->users as $_user) {
            if ($user->id === $_user->id) {
                return true;
            }
        }
        return false;
    }

    public function getAuthType(): int
    {
        if ($this->externalId === null) {
            return \app\models\settings\Site::LOGIN_STD;
        }
        $authparts = explode(':', $this->externalId);
        if (preg_match('/^openslides\-/siu', $authparts[0])) {
            return \app\models\settings\Site::LOGIN_OPENSLIDES;
        }
        return \app\models\settings\Site::LOGIN_STD;
    }

    public function getVotingApiObject(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getNormalizedTitle(),
            'member_count' => count($this->getUserIds()),
        ];
    }

    public function getUserAdminApiObject(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getNormalizedTitle(),
            'description' => $this->getNormalizedDescription(),
            'deletable' => $this->isUserDeletable(),
            'permissions' => $this->getPermissions(),
            'auth_type' => $this->getAuthType(),
        ];
    }

    public function isSpecificallyRelevantForConsultationOrSite(Consultation $consultation): bool
    {
        if ($this->consultationId) {
            return ($this->consultationId === $consultation->id);
        } else {
            return $this->siteId === $consultation->siteId;
        }
    }

    public function isUserDeletable(): bool
    {
        // Automatic template-based user groups and system-wide groups may not be deleted by users
        return $this->templateId === null && ($this->siteId !== null || $this->consultationId !== null);
    }

    public function belongsToExternalAuth(string $authPrefix): bool
    {
        return $this->externalId && (stripos($this->externalId, $authPrefix . ':') === 0);
    }

    public function mayBeUsedForConsultation(Consultation $consultation): bool
    {
        if ($this->consultationId !== null) {
            return $this->consultationId === $consultation->id;
        } elseif ($this->siteId !== null) {
            return $this->siteId === $consultation->siteId;
        } else {
            if ($this->belongsToExternalAuth('gruenesnetz')) {
                return in_array(SiteSettings::LOGIN_GRUENES_NETZ, $consultation->site->getSettings()->loginMethods);
            }
        }

        return false;
    }

    public static function createDefaultGroupSiteAdmin(Site $site): self
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $site->id;
        $group->consultationId = null;
        $group->externalId = null;
        $group->templateId = static::TEMPLATE_SITE_ADMIN;
        $group->title = \Yii::t('user', 'group_template_siteadmin');
        $group->setPermissions([static::PERMISSION_ADMIN_ALL]);
        $group->selectable = 1;
        $group->save();

        return $group;
    }

    public static function createDefaultGroupConsultationAdmin(Consultation $consultation): self
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $consultation->siteId;
        $group->consultationId = $consultation->id;
        $group->externalId = null;
        $group->templateId = static::TEMPLATE_CONSULTATION_ADMIN;
        $group->title = \Yii::t('user', 'group_template_consultationadmin');
        $group->setPermissions([static::PERMISSION_ADMIN_ALL]);
        $group->selectable = 1;
        $group->save();

        return $group;
    }
    public static function createDefaultGroupProposedProcedure(Consultation $consultation): self
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $consultation->siteId;
        $group->consultationId = $consultation->id;
        $group->externalId = null;
        $group->templateId = static::TEMPLATE_PROPOSED_PROCEDURE;
        $group->title = \Yii::t('user', 'group_template_proposed');
        $group->setPermissions([static::PERMISSION_PROPOSED_PROCEDURE]);
        $group->selectable = 1;
        $group->save();

        return $group;
    }
    public static function createDefaultGroupParticipant(Consultation $consultation): self
    {
        $group = new ConsultationUserGroup();
        $group->siteId = $consultation->siteId;
        $group->consultationId = $consultation->id;
        $group->externalId = null;
        $group->templateId = static::TEMPLATE_PARTICIPANT;
        $group->title = \Yii::t('user', 'group_template_participant');
        $group->setPermissions([]);
        $group->selectable = 1;
        $group->save();

        return $group;
    }
}
