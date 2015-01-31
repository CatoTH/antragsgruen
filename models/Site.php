<?php
namespace app\models;

use SiteSettings;
use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $current_consultation_id
 * @property int $public
 * @property string $subdomain
 * @property string $title
 * @property string $title_short
 * @property string $settings
 * @property string $contact
 *
 * @property Consultation $current_consultation
 * @property Consultation[] $consultations
 * @property User[] $namespace_users
 * @property User[] $admins
 */
class Site extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'site';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCurrent_consultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'current_consultation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultations()
    {
        return $this->hasMany(Consultation::className(), ['site_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNamespace_users()
    {
        return $this->hasMany(User::className(), ['site_namespace_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmins()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->viaTable('site_admin', ['site_id' => 'id']);
    }


    /** @var null|SiteSettings */
    private $settings_object = null;

    /**
     * @return SiteSettings
     */
    public function getSettings()
    {
        if (!is_object($this->settings_object)) $this->settings_object = new SiteSettings($this->settings);
        return $this->settings_object;
    }

    /**
     * @param SiteSettings $settings
     */
    public function setSettings($settings)
    {
        $this->settings_object = $settings;
        $this->settings        = $settings->toJSON();
    }


    /**
     * @return Site[]
     */
    public static function getSidebarSites()
    { // @TODO
        /** @var Site[] $sites */
        $sites  = Site::find()->orderBy('id DESC')->all();
        /*
        $reihen2 = array();
        foreach ($reihen as $reihe) {
            if ($reihe->aktuelle_veranstaltung && !$reihe->aktuelle_veranstaltung->getEinstellungen()->wartungs_modus_aktiv) $reihen2[] = $reihe;
        }
        */
        return $sites;
    }

}