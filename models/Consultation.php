<?php

namespace app\models;

use ConsultationSettings;
use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $site_id
 * @property int $type
 *
 * @property string $url_path
 * @property string $title
 * @property string $title_short
 * @property string $event_date_from
 * @property string $event_date_to
 * @property string $deadline_motions
 * @property string $deadline_amendments
 * @property string $policy_motions
 * @property string $policy_amendments
 * @property string $policy_comments
 * @property string $policy_support
 * @property string $admin_email
 * @property string $settings
 *
 * @property Site $site
 * @property Motion[] $motions
 * @property ConsultationText[] $texts
 * @property User[] $admins
 * @property ConsultationOdtTemplate[] $odt_templates
 * @property ConsultationSubscription[] $subscriptions
 * @property ConsultationTag[] $tags
 */
class Consultation extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultation';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::className(), ['id' => 'site_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::className(), ['consultation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexts()
    {
        return $this->hasMany(ConsultationText::className(), ['consultation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmins()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->viaTable('consultation_admin', ['consultation_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOdt_templates()
    {
        return $this->hasMany(ConsultationOdtTemplate::className(), ['id' => 'consultation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(ConsultationSubscription::className(), ['id' => 'consultation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationTag::className(), ['id' => 'consultation_id']);
    }


    /** @var null|ConsultationSettings */
    private $settings_object = null;

    /**
     * @return ConsultationSettings
     */
    public function getSettings()
    {
        if (!is_object($this->settings_object)) $this->settings_object = new ConsultationSettings($this->settings);
        return $this->settings_object;
    }

    /**
     * @param ConsultationSettings $settings
     */
    public function setSettings($settings)
    {
        $this->settings_object = $settings;
        $this->settings        = $settings->toJSON();
    }

}
