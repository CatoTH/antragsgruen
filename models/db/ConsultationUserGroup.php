<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
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

    public const TEMPLATE_SITE_ADMIN = 1;
    public const TEMPLATE_CONSULTATION_ADMIN = 2;
    public const TEMPLATE_PROPOSED_PROCEDURE = 3;
    public const TEMPLATE_PARTICIPANT = 4;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'consultationUserGroup';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'userId'])->viaTable('userGroup', ['groupId' => 'id'])
                    ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
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

    public function addUser(User $user): void
    {
        $this->link('users', $user);
    }

    public function getUserAdminApiObject(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getNormalizedTitle(),
            'permissions' => $this->getPermissions(),
        ];
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
