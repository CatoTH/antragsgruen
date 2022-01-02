<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string|null $externalId
 * @property string $title
 * @property int|null $consultationId
 * @property int|null $siteId
 * @property int $selectable
 * @property string $settings
 *
 * @property Consultation|null $consultation
 * @property Site|null $site
 * @property User[] $users
 */
class ConsultationUserGroup extends ActiveRecord
{
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

    /** @var null|\app\models\settings\ConsultationUserGroup */
    private $settingsObject = null;

    public function getSettings(): \app\models\settings\ConsultationUserGroup
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new \app\models\settings\ConsultationUserGroup($this->settings);
        }

        return $this->settingsObject;
    }

    public function setSettings(?\app\models\settings\ConsultationUserGroup $settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }
}
